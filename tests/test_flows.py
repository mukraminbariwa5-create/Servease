"""End-to-end HTTP tests: sign-up, jobs, quotes, bookings, payments, reviews, messaging, security and admin tools.
Run against a freshly installed demo database:  php database/install.php && python3 tests/test_flows.py
"""
import os, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
B = os.environ.get('SERVEASE_URL', 'http://127.0.0.1:8000').rstrip('/')
MAIL_LOG = ROOT / 'storage' / 'logs' / 'mail.log'
DB_HELPER = str(ROOT / 'tests' / 'db.php')
import requests, re, json, subprocess, time
ok=0; fail=0
def check(cond, msg):
    global ok, fail
    if cond: ok+=1; print('  ✓', msg)
    else: fail+=1; print('  ✗ FAIL:', msg)
def q(sql): return json.loads(subprocess.check_output(['php',DB_HELPER,sql]))
def tok(s, path='/'):
    r=s.get(B+path); m=re.search(r'name="csrf-token" content="([^"]+)"',r.text) or re.search(r'name="_token" value="([^"]+)"',r.text); return m.group(1)
def post(s, path, data=None, files=None, json_accept=False, token=True):
    data=dict(data or {})
    if token: data['_token']=tok(s)
    h={'Accept':'application/json'} if json_accept else {}
    return s.post(B+path, data=data, files=files, headers=h, allow_redirects=True)
def login(email, pw='servease123'):
    s=requests.Session(); r=post(s,'/login',{'email':email,'password':pw}); return s, r
def flashes(r): return re.findall(r'class="alert alert-(\w+) toast"[^>]*>.*?<div class="grow">(.*?)</div>', r.text, re.S)

print('1. Registration & email verification')
c=requests.Session()
r=post(c,'/register',{'role':'client','first_name':'Test','last_name':'Client','email':'tclient@example.com','phone':'09171234567','city':'Pasig','password':'secret123','password_confirmation':'secret123','terms':'1'})
check(r.url.rstrip('/')==B, f'client registered → lands on Home ({r.url})')
link=re.findall(r'(http\S*verify-email\?token=\S+)', open(MAIL_LOG).read())[-1]
r=c.get(link.replace('http://127.0.0.1:8000','').join([B,'']) if link.startswith('/') else link)
check(q("SELECT email_verified_at FROM users WHERE email='tclient@example.com'")[0]['email_verified_at'] is not None, 'email verified via link')
r=post(requests.Session(),'/register',{'role':'client','first_name':'X','last_name':'Y','email':'tclient@example.com','password':'secret123','password_confirmation':'secret123','terms':'1'})
check('/register' in r.url, 'duplicate email rejected')
r=post(requests.Session(),'/register',{'role':'client','first_name':'X','last_name':'Y','email':'weak@example.com','password':'short','password_confirmation':'short','terms':'1'})
check('/register' in r.url, 'weak password rejected')

p=requests.Session()
plumb=q("SELECT id FROM categories WHERE slug='plumbing'")[0]['id']
r=post(p,'/register',{'role':'provider','first_name':'Test','last_name':'Pro','email':'tpro@example.com','city':'Pasig','category_id':plumb,'password':'secret123','password_confirmation':'secret123','terms':'1'})
check(r.status_code==200 and 'login' not in r.url, f'provider registered → {r.url}')
pid=q("SELECT id FROM users WHERE email='tpro@example.com'")[0]['id']

print('2. Provider profile, services, portfolio, verification')
r=post(p,'/provider/profile',{'category_id':plumb,'years_experience':'6','headline':'Test plumber for leaks','bio':'I fix leaks fast and clean up after every single job.','city':'Pasig','service_area':'Pasig, Marikina','hourly_rate':'500','skills':'Leaks, Pipes'})
check(q(f"SELECT hourly_rate FROM provider_profiles WHERE user_id={pid}")[0]['hourly_rate'] in ('500.00',500,500.0), 'profile saved')
r=post(p,'/provider/services',{'title':'Leak fix','price':'900','price_unit':'starting','description':'Any leak'})
check(len(q(f"SELECT id FROM provider_services WHERE provider_id={pid}"))==1, 'service added')
r=post(p,'/provider/portfolio',{'caption':'Kitchen job'},files={'image':('a.jpg',open(ROOT/'tests'/'fixtures'/'photo.jpg','rb'),'image/jpeg')})
check(len(q(f"SELECT id FROM portfolio_items WHERE provider_id={pid}"))==1, 'portfolio photo uploaded')
r=post(p,'/provider/portfolio',{'caption':'bad'},files={'image':('evil.php.jpg',b'<?php echo 1; ?>','image/jpeg')})
check(len(q(f"SELECT id FROM portfolio_items WHERE provider_id={pid}"))==1, 'fake image (php code) rejected')
post(p,'/settings/avatar',{'return':'/provider/profile'},files={'avatar':('me.jpg',open(ROOT/'tests'/'fixtures'/'photo.jpg','rb'),'image/jpeg')})
r=post(p,'/provider/verification',{},files={'document':('id.pdf',open(ROOT/'tests'/'fixtures'/'id.pdf','rb'),'application/pdf')})
check(q(f"SELECT verification_status FROM provider_profiles WHERE user_id={pid}")[0]['verification_status']=='pending', 'verification submitted → pending')
r=requests.get(B+'/storage/private/verification/sample-id.pdf',allow_redirects=False)
check(r.status_code in (302,303,403,404) and b'%PDF' not in r.content, f'private documents not publicly reachable ({r.status_code})')
r=p.get(B+'/storage/private/verification/sample-id.pdf',allow_redirects=False)
check(r.status_code in (403,404) and b'%PDF' not in r.content, f'private documents not reachable when signed in either ({r.status_code})')
r=requests.get(B+'/services',allow_redirects=False)
check(r.status_code in (302,303) and '/welcome' in r.headers.get('Location',''), 'signed-out visitors start at the intro')
r=requests.get(B+'/services',allow_redirects=False,cookies={'se_intro':'1'})
check(r.status_code in (302,303) and '/login' in r.headers.get('Location',''), 'returning signed-out visitors go straight to log in')

print('3. Admin verifies provider')
a,_=login('admin@servease.test')
r=a.get(B+f'/admin/verifications/{pid}/document'); check(r.status_code==200 and r.content[:4]==b'%PDF', 'admin can view document')
r=c.get(B+f'/admin/verifications/{pid}/document'); check(r.status_code in (403,404), 'client cannot view document')
post(a,f'/admin/verifications/{pid}',{'decision':'approve'})
check(q(f"SELECT verification_status FROM provider_profiles WHERE user_id={pid}")[0]['verification_status']=='verified', 'provider verified')

print('4. Job → quote → accept → booking')
r=post(c,'/jobs',{'category_id':plumb,'title':'Leaking bathroom pipe','description':'Pipe under bathroom sink leaks constantly, need repair asap.','city':'Pasig','preferred_date':time.strftime('%Y-%m-%d',time.localtime(time.time()+86400*3)),'budget_min':'800','budget_max':'1500','urgency':'urgent'})
job=q("SELECT * FROM jobs WHERE title='Leaking bathroom pipe'")
check(len(job)==1, f'job posted → {r.url}'); jid=job[0]['id']
r=post(p,f'/jobs/{jid}/quote',{'amount':'1200','days_needed':'1','message':'I can fix this tomorrow morning, includes parts.'})
qt=q(f"SELECT * FROM quotes WHERE job_id={jid}"); check(len(qt)==1, 'quote sent'); qid=qt[0]['id']
check(q(f"SELECT quotes_count FROM jobs WHERE id={jid}")[0]['quotes_count']==1, 'job quote count updated')
r=post(p,f'/quotes/{qid}/accept',{}); check(q(f"SELECT status FROM quotes WHERE id={qid}")[0]['status']=='pending', 'provider cannot accept own quote')
other,_=login('patricia.uy@servease.test')
post(other,f'/quotes/{qid}/accept',{}); check(q(f"SELECT status FROM quotes WHERE id={qid}")[0]['status']=='pending', 'other client cannot accept the quote')
# Accepting opens a scheduling step: only the pro's open days and times, plus the address
html=c.get(B+f'/quotes/{qid}/accept').text
open_dates=re.findall(r'class="date-cell(?! is-full)[^"]*" data-fill="#[\w-]+" data-value="([\d-]+)"',html)
check(len(open_dates)>0, f'accept page lists the pro\'s open dates ({len(open_dates)})')
post(c,f'/quotes/{qid}/accept',{'scheduled_date':open_dates[0],'scheduled_time':'10:00','hours':'2','address':'','city':'Pasig'})
check(len(q(f"SELECT id FROM bookings WHERE quote_id={qid}"))==0, 'accepting without an address is rejected')
post(c,f'/quotes/{qid}/accept',{'scheduled_date':'2020-01-01','scheduled_time':'10:00','hours':'2','address':'12 Test St','city':'Pasig'})
check(len(q(f"SELECT id FROM bookings WHERE quote_id={qid}"))==0, 'accepting with a past date is rejected')
r=post(c,f'/quotes/{qid}/accept',{'scheduled_date':open_dates[0],'scheduled_time':'10:00','hours':'2','address':'12 Test St, Brgy. Kapitolyo','city':'Pasig'})
bk=q(f"SELECT * FROM bookings WHERE quote_id={qid}")
check(len(bk)==1 and bk[0]['status']=='accepted', f'quote accepted → booking {bk[0]["status"] if bk else None}'); bid=bk[0]['id']
check(bk[0]['scheduled_time']=='10:00' and bk[0]['address'] and bk[0]['duration_hours']==2, 'booking from a quote has a time, length and address')
check(q(f"SELECT status FROM jobs WHERE id={jid}")[0]['status']=='assigned', 'job marked assigned')

print('5. Booking lifecycle, payment, review')
r=other.get(B+f'/bookings/{bid}'); check(r.status_code in (403,404), f'other client cannot see booking ({r.status_code})')
post(c,f'/bookings/{bid}/status',{'action':'start'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='accepted', 'client cannot mark job started')
post(p,f'/bookings/{bid}/status',{'action':'start'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='accepted', 'pro cannot start before the booked day')
post(p,f'/bookings/{bid}/status',{'action':'complete'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='accepted', 'pro cannot complete before the booked day')
q(f"UPDATE bookings SET scheduled_date=CURDATE() WHERE id={bid}")  # the day arrives
post(p,f'/bookings/{bid}/status',{'action':'start'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='in_progress', 'pro started job on the day')
post(c,f'/bookings/{bid}/review',{'rating':'5','comment':'early'}); check(len(q(f"SELECT id FROM reviews WHERE booking_id={bid}"))==0, 'cannot review before completion')
post(p,f'/bookings/{bid}/status',{'action':'complete'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='completed', 'pro completed job')
post(c,f'/bookings/{bid}/pay',{'method':'gcash','reference':''})
check(len(q(f"SELECT id FROM payments WHERE booking_id={bid}"))==0, 'GCash payment without a reference number is rejected')
post(c,f'/bookings/{bid}/pay',{'method':'gcash','reference':'GC-777'})
pay=q(f"SELECT * FROM payments WHERE booking_id={bid}"); check(len(pay)==1 and pay[0]['status']=='pending', 'client recorded GCash payment (pending)')
post(c,f'/payments/{pay[0]["id"]}/confirm',{}); check(q(f"SELECT status FROM payments WHERE booking_id={bid}")[0]['status']=='pending', 'client cannot confirm own payment')
post(p,f'/payments/{pay[0]["id"]}/confirm',{})
check(q(f"SELECT payment_status FROM bookings WHERE id={bid}")[0]['payment_status']=='paid', 'pro confirmed receipt → booking paid')
post(c,f'/bookings/{bid}/review',{'rating':'5','comment':'Fixed quickly and cleaned up. Great!'})
check(len(q(f"SELECT id FROM reviews WHERE booking_id={bid}"))==1, 'client left review')
post(c,f'/bookings/{bid}/review',{'rating':'1','comment':'dup'}); check(len(q(f"SELECT id FROM reviews WHERE booking_id={bid}"))==1, 'only one review per booking')
pp=q(f"SELECT rating_avg, rating_count, jobs_completed FROM provider_profiles WHERE user_id={pid}")[0]
check(float(pp['rating_avg'])==5.0 and pp['rating_count']==1 and pp['jobs_completed']==1, f'pro stats updated {pp}')
rid=q(f"SELECT id FROM reviews WHERE booking_id={bid}")[0]['id']
post(p,f'/reviews/{rid}/reply',{'reply':'Thank you!'}); check(q(f"SELECT provider_reply FROM reviews WHERE id={rid}")[0]['provider_reply']=='Thank you!', 'pro replied to review')

print('6. Working hours, direct booking request, decline, cancel')
post(p,'/provider/hours',{'work_days[]':['1','2','3','4','5'],'work_start':'09:00','work_end':'17:00'})
pp=q(f"SELECT work_days, work_start, work_end FROM provider_profiles WHERE user_id={pid}")[0]
check(pp['work_days']=='1,2,3,4,5' and str(pp['work_start']).startswith('09:00') and str(pp['work_end']).startswith('17:00'), f'pro saved working hours {pp}')
post(p,'/provider/hours',{'work_days[]':['1'],'work_start':'15:00','work_end':'09:00'})
check(q(f"SELECT work_start FROM provider_profiles WHERE user_id={pid}")[0]['work_start'].startswith('09:00'), 'end before start rejected')
post(p,'/provider/hours',{'work_start':'09:00','work_end':'17:00'})
check(q(f"SELECT work_days FROM provider_profiles WHERE user_id={pid}")[0]['work_days']=='1,2,3,4,5', 'no working days rejected')
def next_day(weekdays, start=1):
    t=time.time()+86400*start
    while time.localtime(t).tm_wday+1 not in weekdays: t+=86400
    return time.strftime('%Y-%m-%d',time.localtime(t))
html=c.get(B+f'/providers/{pid}/book').text
shown=re.findall(r'class="date-cell[^"]*" data-fill="#scheduled_date" data-value="([\d-]+)"',html)
check(shown and all(time.strptime(x,'%Y-%m-%d').tm_wday<5 for x in shown), f'booking calendar shows only working days ({len(shown)})')
check('value="08:00"' not in html and 'value="17:00"' not in html and 'value="16:00"' in html, 'time chips stay inside working hours')
post(c,f'/providers/{pid}/book',{'title':'Weekend job','details':'Saturday visit please','scheduled_date':next_day([6]),'scheduled_time':'10:00','address':'12 Test St','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Weekend job'"))==0, 'booking on a day off rejected')
post(c,f'/providers/{pid}/book',{'title':'Early job','details':'Very early visit please','scheduled_date':next_day([1,2,3,4,5]),'scheduled_time':'08:00','address':'12 Test St','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Early job'"))==0, 'booking outside working hours rejected')
svc=q(f"SELECT id FROM provider_services WHERE provider_id={pid}")[0]['id']
d=next_day([1,2,3,4,5],3)
post(c,f'/providers/{pid}/book',{'service_id':svc,'title':'Fix kitchen faucet','details':'Dripping faucet','scheduled_date':d,'scheduled_time':'10:00','address':'12 Test St','city':'Pasig','amount':'900'})
b2=q(f"SELECT * FROM bookings WHERE title='Fix kitchen faucet'"); check(len(b2)==1 and b2[0]['status']=='requested','booking requested')
post(c,f'/providers/{pid}/book',{'title':'Double booked','details':'Same slot as the faucet','scheduled_date':d,'scheduled_time':'10:00','address':'12 Test St','city':'Pasig','amount':'900'})
check(len(q("SELECT id FROM bookings WHERE title='Double booked'"))==0, 'a taken time slot cannot be booked twice')
html=c.get(B+f'/providers/{pid}/book').text.replace('&quot;','"')
dur=q(f"SELECT duration_hours, amount FROM bookings WHERE title='Fix kitchen faucet'")[0]
check(f'"{d}":[[10,{10+int(dur["duration_hours"])}]]' in html, 'booking page marks the busy hours')
svcrow=q(f"SELECT price, duration_hours FROM provider_services WHERE id={svc}")[0]
check(float(dur['amount'])==float(svcrow['price']) and dur['duration_hours']==svcrow['duration_hours'], 'fixed-price booking uses the service price and length, not the posted budget')
post(c,f'/providers/{pid}/book',{'title':'Overlap job','details':'Starts during the faucet job','scheduled_date':d,'scheduled_time':f'{10+int(dur["duration_hours"])-1:02d}:00' if int(dur['duration_hours'])>1 else '10:00','hours':'1','address':'12 Test St','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Overlap job'"))==0, 'a job that overlaps another booking is rejected')
post(c,f'/providers/{pid}/book',{'title':'Too long job','details':'Eight hours starting mid-afternoon','scheduled_date':d,'scheduled_time':'14:00','hours':'8','address':'12 Test St','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Too long job'"))==0, 'a job that would run past closing is rejected')
post(p,'/provider/days-off',{'day':d})
check(len(q(f"SELECT day FROM provider_days_off WHERE provider_id={pid}"))==0, 'cannot block a date that already has a booking')
d_off=next_day([1,2,3,4,5],10)
post(p,'/provider/days-off',{'day':d_off})
check(len(q(f"SELECT day FROM provider_days_off WHERE provider_id={pid} AND day='{d_off}'"))==1, 'pro blocked a day off')
html=c.get(B+f'/providers/{pid}/book').text
check(f'data-value="{d_off}"' not in html, 'blocked day disappears from the booking calendar')
post(c,f'/providers/{pid}/book',{'title':'Day off job','details':'Booked on a blocked date','scheduled_date':d_off,'scheduled_time':'10:00','hours':'1','address':'12 Test St','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Day off job'"))==0, 'booking on a blocked day is rejected')
post(p,f'/provider/days-off/{d_off}/delete')
check(len(q(f"SELECT day FROM provider_days_off WHERE provider_id={pid}"))==0, 'pro reopened the day')
# Reschedule: the customer proposes, the pro accepts; only free slots are allowed
b2id=b2[0]['id']
post(c,f'/bookings/{b2id}/reschedule',{'scheduled_date':d,'scheduled_time':'10:00'})
check(q(f"SELECT proposed_date FROM bookings WHERE id={b2id}")[0]['proposed_date'] is None, 'proposing the current time is rejected')
post(c,f'/bookings/{b2id}/reschedule',{'scheduled_date':d,'scheduled_time':'15:00','note':'Afternoon is better'})
check(q(f"SELECT proposed_time FROM bookings WHERE id={b2id}")[0]['proposed_time']=='15:00', 'customer proposed a new time')
post(c,f'/bookings/{b2id}/reschedule/answer',{'answer':'accept'})
check(q(f"SELECT scheduled_time FROM bookings WHERE id={b2id}")[0]['scheduled_time']=='10:00', 'proposer cannot accept their own proposal')
post(p,f'/bookings/{b2id}/reschedule/answer',{'answer':'accept'})
row=q(f"SELECT scheduled_time, proposed_date FROM bookings WHERE id={b2id}")[0]
check(row['scheduled_time']=='15:00' and row['proposed_date'] is None, 'pro accepted → booking moved to the new time')
post(p,f'/bookings/{b2id}/status',{'action':'decline'}); check(q(f"SELECT status FROM bookings WHERE id={b2id}")[0]['status']=='requested','declining without a reason is rejected')
post(p,f'/bookings/{b2id}/status',{'action':'decline','reason':'booked','note':'Away that week'})
row=q(f"SELECT status, cancel_reason FROM bookings WHERE id={b2id}")[0]
check(row['status']=='declined' and row['cancel_reason'].startswith("I'm fully booked"),'pro declined with a reason')
post(c,f'/providers/{pid}/book',{'title':'Fix shower','details':'Weak pressure','scheduled_date':d,'scheduled_time':'14:00','address':'12 Test St','city':'Pasig','amount':'700'})
b3=q("SELECT * FROM bookings WHERE title='Fix shower'")[0]
check(float(b3['amount'])==700 and b3['duration_hours']==2, 'custom job keeps the budget and the default 2-hour length')
post(c,f'/bookings/{b3["id"]}/status',{'action':'cancel','reason':'other'}); check(q(f"SELECT status FROM bookings WHERE id={b3['id']}")[0]['status']=='requested','"Something else" without details is rejected')
post(c,f'/bookings/{b3["id"]}/status',{'action':'cancel','reason':'other','note':'Fixed it myself'}); check(q(f"SELECT status FROM bookings WHERE id={b3['id']}")[0]['status']=='cancelled','client cancelled with a reason')
post(c,f'/providers/{pid}/book',{'title':'Past date','details':'x','scheduled_date':'2020-01-01','scheduled_time':'14:00','address':'x','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Past date'"))==0,'past date rejected')
post(p,f'/providers/{pid}/book',{'title':'Self','details':'x','scheduled_date':d,'scheduled_time':'14:00','address':'x','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Self'"))==0,'pros cannot book themselves')

print('6b. Installable app: manifest, service worker, offline screen')
r=requests.get(B+'/manifest.webmanifest'); m=r.json() if r.ok else {}
check(r.ok and 'manifest' in r.headers.get('Content-Type','') and m.get('display')=='standalone' and any(i.get('purpose')=='maskable' for i in m.get('icons',[])), 'manifest: standalone app with maskable icon')
check(m.get('start_url','').startswith(m.get('scope','#')) and len(m.get('shortcuts',[]))>=3, 'manifest: start page inside scope, home-screen shortcuts')
r=requests.get(B+'/sw.js')
check(r.ok and 'javascript' in r.headers.get('Content-Type','') and r.headers.get('Service-Worker-Allowed','').endswith('/') and '__CACHE__' not in r.text, 'service worker served with its scope header')
r=requests.get(B+'/offline',allow_redirects=False)
check(r.status_code==200 and "offline" in r.text.lower(), 'offline screen is public')
r=requests.head(B+'/login'); check(r.status_code==200, 'HEAD requests answer like GET')

print('7. Messaging')
r=post(c,'/messages/start',{'user_id':pid}); m=re.search(r'/messages/(\d+)',r.url); check(bool(m), f'conversation opened {r.url}')
cid=m.group(1)
r=post(c,f'/messages/{cid}',{'body':'Hi! Thanks again <script>alert(1)</script>'},json_accept=True)
check(r.status_code==200 and r.json().get('message'), 'message sent via AJAX')
r=p.get(B+f'/messages/{cid}/poll?after=0',headers={'Accept':'application/json'}); msgs=r.json()['messages']
check(any('Thanks again' in x['body'] for x in msgs), 'pro receives message via poll')
r=p.get(B+f'/messages/{cid}'); check('<script>alert(1)</script>' not in r.text, 'message HTML is escaped (no XSS)')
r=other.get(B+f'/messages/{cid}'); check(r.status_code in (403,404), 'outsider cannot read conversation')

print('8. Account settings, password reset, security')
post(c,'/settings',{'first_name':'Tess','last_name':'Client','email':'tclient@example.com','phone':'09171234567','city':'Makati'})
check(q("SELECT first_name FROM users WHERE email='tclient@example.com'")[0]['first_name']=='Tess','profile updated')
post(c,'/settings/avatar',{},files={'avatar':('me.jpg',open(ROOT/'tests'/'fixtures'/'photo.jpg','rb'),'image/jpeg')})
check(q("SELECT avatar FROM users WHERE email='tclient@example.com'")[0]['avatar'] is not None,'avatar uploaded')
post(c,'/settings/password',{'current_password':'wrong','password':'newpass123','password_confirmation':'newpass123'})
s,r=login('tclient@example.com','newpass123'); check('/login' in r.url,'password not changed with wrong current password')
post(c,'/settings/password',{'current_password':'secret123','password':'newpass123','password_confirmation':'newpass123'})
s,r=login('tclient@example.com','newpass123'); check('/login' not in r.url,'password changed')
post(requests.Session(),'/forgot-password',{'email':'tclient@example.com'})
link=re.findall(r'(http\S*reset-password\?token=\S+)', open(MAIL_LOG).read())[-1]
token=re.search(r'token=([^&\s]+)',link).group(1)
rs=requests.Session(); rs.get(B+'/reset-password?token='+token)
post(rs,'/reset-password',{'token':token,'email':'tclient@example.com','password':'reset1234','password_confirmation':'reset1234'})
s,r=login('tclient@example.com','reset1234'); check('/login' not in r.url,'password reset via emailed link')
post(requests.Session(),'/reset-password',{'token':token,'email':'tclient@example.com','password':'again1234','password_confirmation':'again1234'})
s,r=login('tclient@example.com','again1234'); check('/login' in r.url,'reset link cannot be reused')
r=requests.post(B+'/jobs',data={'title':'x'},allow_redirects=False); check(r.status_code in (302,303,419), f'POST without CSRF token rejected ({r.status_code})')
for i in range(6): s,r=login('client@servease.test','wrongpass')
s,r=login('client@servease.test'); check('/login' in r.url,'login throttled after repeated failures')
subprocess.run(['php',DB_HELPER,"DELETE FROM login_attempts"],capture_output=True)

print('8b. Problem reports, search suggestions, reminders')
post(other,f'/bookings/{bid}/report',{'reason':'quality','details':'Not my booking, should be refused by the server.'})
check(len(q(f"SELECT id FROM booking_issues WHERE booking_id={bid}"))==0, 'outsiders cannot report a booking')
post(c,f'/bookings/{bid}/report',{'reason':'quality','details':'short'})
check(len(q(f"SELECT id FROM booking_issues WHERE booking_id={bid}"))==0, 'report needs real details')
post(c,f'/bookings/{bid}/report',{'reason':'quality','details':'The pipe started leaking again the next day.'})
iss=q(f"SELECT * FROM booking_issues WHERE booking_id={bid}"); check(len(iss)==1 and iss[0]['status']=='open', 'customer reported a problem')
post(c,f'/bookings/{bid}/report',{'reason':'quality','details':'Second report while the first is still open.'})
check(len(q(f"SELECT id FROM booking_issues WHERE booking_id={bid}"))==1, 'one open report per person per booking')
r=c.get(B+'/search/suggest?q=plum',headers={'Accept':'application/json'}); items=r.json().get('items',[])
check(any(i['type']=='Category' and 'Plumbing' in i['label'] for i in items), 'search suggestions find the Plumbing category')
check(not any('repair' in i['label'].lower() and 'air' not in i['label'].lower().split()[0] for i in c.get(B+'/search/suggest?q=air').json()['items'] if i['type']=='Service'), 'suggestions match word starts ("air" ≠ "repair")')
q(f"UPDATE bookings SET status='accepted', scheduled_date=DATE_ADD(CURDATE(), INTERVAL 1 DAY), reminder_sent_at=NULL WHERE id={b2id}")
before=MAIL_LOG.read_text(errors='ignore').count('Subject: Reminder') if MAIL_LOG.exists() else 0
out=subprocess.check_output(['php',str(ROOT/'database'/'reminders.php')]).decode()
after=MAIL_LOG.read_text(errors='ignore').count('Subject: Reminder')
check(after-before>=2 and q(f"SELECT reminder_sent_at FROM bookings WHERE id={b2id}")[0]['reminder_sent_at'], f'day-before reminders emailed to both sides ({out.strip()})')
subprocess.check_output(['php',str(ROOT/'database'/'reminders.php')])
check(MAIL_LOG.read_text(errors='ignore').count('Subject: Reminder')==after, 'each booking is reminded only once')

print('9. Admin tools')
tid=q("SELECT id FROM users WHERE email='tclient@example.com'")[0]['id']
post(a,f'/admin/users/{tid}/status',{})
check(q(f"SELECT status FROM users WHERE id={tid}")[0]['status']=='suspended','admin suspended user')
r=c.get(B+'/dashboard',allow_redirects=False); check(r.status_code in (302,303),'suspended user is signed out')
s,r=login('tclient@example.com','reset1234'); check('/login' in r.url,'suspended user cannot sign in')
post(a,f'/admin/users/{tid}/status',{}); check(q(f"SELECT status FROM users WHERE id={tid}")[0]['status']=='active','admin reactivated user')
post(a,'/admin/categories',{'name':'Pest Control','icon':'shield','description':'Termites and pests','sort_order':'13'})
cat=q("SELECT * FROM categories WHERE name='Pest Control'"); check(len(cat)==1,'category added')
post(a,f'/admin/categories/{cat[0]["id"]}/toggle',{}); check(q(f"SELECT is_active FROM categories WHERE id={cat[0]['id']}")[0]['is_active']==0,'category hidden')
post(a,f'/admin/reviews/{rid}/toggle',{})
check(q(f"SELECT rating_count FROM provider_profiles WHERE user_id={pid}")[0]['rating_count']==0,'hidden review removed from pro rating')
post(a,f'/admin/reviews/{rid}/toggle',{})
r=c.get(B+'/admin'); r2=p.get(B+'/admin'); check(r2.status_code==403,'non-admins blocked from admin panel')
iid=iss[0]['id']
post(c,f'/admin/issues/{iid}/resolve',{'admin_note':'Trying to resolve my own report'}); check(q(f"SELECT status FROM booking_issues WHERE id={iid}")[0]['status']=='open','only admins resolve reports')
post(a,f'/admin/issues/{iid}/resolve',{'admin_note':'Pro will return on Saturday to redo the joint for free.'})
check(q(f"SELECT status FROM booking_issues WHERE id={iid}")[0]['status']=='resolved','admin resolved the report with a note')
print(f'\n{ok} passed, {fail} failed')
