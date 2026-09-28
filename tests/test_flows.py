"""End-to-end HTTP tests: sign-up, jobs, quotes, bookings, payments, reviews, messaging, security and admin tools.
Run against a freshly installed demo database:  php database/install.php && python3 tests/test_flows.py
"""
import os, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
B = os.environ.get('FIXORIA_URL', 'http://127.0.0.1:8000').rstrip('/')
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
def login(email, pw='fixoria123'):
    s=requests.Session(); r=post(s,'/login',{'email':email,'password':pw}); return s, r
def flashes(r): return re.findall(r'class="alert alert-(\w+) toast"[^>]*>.*?<div class="grow">(.*?)</div>', r.text, re.S)

print('1. Registration & email verification')
c=requests.Session()
r=post(c,'/register',{'role':'client','first_name':'Test','last_name':'Client','email':'tclient@example.com','phone':'09171234567','city':'Pasig','password':'secret123','password_confirmation':'secret123','terms':'1'})
check('/dashboard' in r.url, f'client registered → {r.url}')
link=re.findall(r'(http\S*verify-email\?token=\S+)', open(MAIL_LOG).read())[-1]
r=c.get(link.replace('http://127.0.0.1:8000','').join([B,'']) if link.startswith('/') else link)
check(q("SELECT email_verified_at FROM users WHERE email='tclient@example.com'")[0]['email_verified_at'] is not None, 'email verified via link')
r=post(requests.Session(),'/register',{'role':'client','first_name':'X','last_name':'Y','email':'tclient@example.com','password':'secret123','password_confirmation':'secret123','terms':'1'})
check('/dashboard' not in r.url, 'duplicate email rejected')
r=post(requests.Session(),'/register',{'role':'client','first_name':'X','last_name':'Y','email':'weak@example.com','password':'short','password_confirmation':'short','terms':'1'})
check('/dashboard' not in r.url, 'weak password rejected')

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
r=post(p,'/provider/verification',{},files={'document':('id.pdf',open(ROOT/'tests'/'fixtures'/'id.pdf','rb'),'application/pdf')})
check(q(f"SELECT verification_status FROM provider_profiles WHERE user_id={pid}")[0]['verification_status']=='pending', 'verification submitted → pending')
r=requests.get(B+'/storage/private/verification/sample-id.pdf')
check(r.status_code in (403,404), f'private documents not publicly reachable ({r.status_code})')

print('3. Admin verifies provider')
a,_=login('admin@fixoria.test')
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
other,_=login('patricia.uy@fixoria.test')
post(other,f'/quotes/{qid}/accept',{}); check(q(f"SELECT status FROM quotes WHERE id={qid}")[0]['status']=='pending', 'other client cannot accept the quote')
r=post(c,f'/quotes/{qid}/accept',{})
bk=q(f"SELECT * FROM bookings WHERE quote_id={qid}")
check(len(bk)==1 and bk[0]['status']=='accepted', f'quote accepted → booking {bk[0]["status"] if bk else None}'); bid=bk[0]['id']
check(q(f"SELECT status FROM jobs WHERE id={jid}")[0]['status']=='assigned', 'job marked assigned')

print('5. Booking lifecycle, payment, review')
r=other.get(B+f'/bookings/{bid}'); check(r.status_code in (403,404), f'other client cannot see booking ({r.status_code})')
post(c,f'/bookings/{bid}/status',{'action':'start'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='accepted', 'client cannot mark job started')
post(p,f'/bookings/{bid}/status',{'action':'start'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='in_progress', 'pro started job')
post(c,f'/bookings/{bid}/review',{'rating':'5','comment':'early'}); check(len(q(f"SELECT id FROM reviews WHERE booking_id={bid}"))==0, 'cannot review before completion')
post(p,f'/bookings/{bid}/status',{'action':'complete'}); check(q(f"SELECT status FROM bookings WHERE id={bid}")[0]['status']=='completed', 'pro completed job')
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

print('6. Direct booking request, decline, cancel')
svc=q(f"SELECT id FROM provider_services WHERE provider_id={pid}")[0]['id']
d=time.strftime('%Y-%m-%d',time.localtime(time.time()+86400*5))
post(c,f'/providers/{pid}/book',{'service_id':svc,'title':'Fix kitchen faucet','details':'Dripping faucet','scheduled_date':d,'scheduled_time':'10:00','address':'12 Test St','city':'Pasig','amount':'900'})
b2=q(f"SELECT * FROM bookings WHERE title='Fix kitchen faucet'"); check(len(b2)==1 and b2[0]['status']=='requested','booking requested')
post(p,f'/bookings/{b2[0]["id"]}/status',{'action':'decline','note':'Fully booked that day'}); check(q(f"SELECT status FROM bookings WHERE id={b2[0]['id']}")[0]['status']=='declined','pro declined')
post(c,f'/providers/{pid}/book',{'title':'Fix shower','details':'Weak pressure','scheduled_date':d,'scheduled_time':'14:00','address':'12 Test St','city':'Pasig','amount':'700'})
b3=q("SELECT * FROM bookings WHERE title='Fix shower'")[0]
post(c,f'/bookings/{b3["id"]}/status',{'action':'cancel','note':'Fixed it myself'}); check(q(f"SELECT status FROM bookings WHERE id={b3['id']}")[0]['status']=='cancelled','client cancelled')
post(c,f'/providers/{pid}/book',{'title':'Past date','details':'x','scheduled_date':'2020-01-01','scheduled_time':'14:00','address':'x','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Past date'"))==0,'past date rejected')
post(p,f'/providers/{pid}/book',{'title':'Self','details':'x','scheduled_date':d,'scheduled_time':'14:00','address':'x','city':'Pasig','amount':'700'})
check(len(q("SELECT id FROM bookings WHERE title='Self'"))==0,'pros cannot book themselves')

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
for i in range(6): s,r=login('client@fixoria.test','wrongpass')
s,r=login('client@fixoria.test'); check('/login' in r.url,'login throttled after repeated failures')
subprocess.run(['php',DB_HELPER,"DELETE FROM login_attempts"],capture_output=True)

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
print(f'\n{ok} passed, {fail} failed')
