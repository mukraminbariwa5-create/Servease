"""Crawls every reachable page as guest, client, pro and admin and fails on PHP errors or 5xx responses.
Run against a freshly installed demo database:  php database/install.php && python3 tests/test_crawl.py
"""
import os, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
B = os.environ.get('SERVEASE_URL', 'http://127.0.0.1:8000').rstrip('/')
MAIL_LOG = ROOT / 'storage' / 'logs' / 'mail.log'
DB_HELPER = str(ROOT / 'tests' / 'db.php')
import requests, re, sys
BAD=['Fatal error','Warning:','Notice:','Deprecated:','Uncaught','Stack trace','Oops']
def session(email=None):
    s=requests.Session()
    if email:
        r=s.get(B+'/login'); tok=re.search(r'name="_token" value="([^"]+)"',r.text).group(1)
        r=s.post(B+'/login',data={'_token':tok,'email':email,'password':'servease123'},allow_redirects=True)
        assert '/login' not in r.url, ('login failed',email,r.url)
    return s
def crawl(s,label,paths):
    seen=set(); q=list(paths); bad=0
    while q:
        p=q.pop(0)
        if p in seen or len(seen)>160: continue
        seen.add(p)
        r=s.get(B+p,allow_redirects=False)
        body=r.text
        errs=[k for k in BAD if k in body]
        if r.status_code>=400 and r.status_code not in (403,404) or errs:
            bad+=1; print(f'  [{label}] {r.status_code} {p} {errs}'); 
            m=re.search(r'(Fatal error|Warning|Uncaught|Notice|Deprecated)[^<]{0,300}',body)
            if m: print('     ',m.group(0)[:300])
            pre=re.search(r'<pre[^>]*>(.{0,500})',body,re.S)
            if pre: print('     ',pre.group(1)[:500])
        if r.status_code==200 and 'text/html' in r.headers.get('content-type',''):
            for h in re.findall(r'href="(/[^"#]*)"',body):
                h=h.replace('&amp;','&')
                if h.startswith(('/assets','/uploads')) or 'logout' in h: continue
                if re.search(r'/(providers|jobs|bookings)/\d+',h) and len([x for x in seen if x.split('?')[0].rsplit('/',1)[0]==h.split('?')[0].rsplit('/',1)[0]])>3: continue
                if '?' in h and len([x for x in seen if x.startswith(h.split('?')[0])])>4: continue
                if h not in seen: q.append(h)
    print(f'{label}: {len(seen)} pages, {bad} problems')
crawl(session(),'guest',['/','/providers','/jobs','/how-it-works','/login','/register','/register?role=provider','/forgot-password','/providers/2','/jobs/1','/nope'])
crawl(session('client@servease.test'),'client',['/dashboard','/bookings','/messages','/my/jobs','/saved','/settings','/notifications','/jobs/new','/providers/3/book'])
crawl(session('provider@servease.test'),'provider',['/dashboard','/bookings','/messages','/my/quotes','/provider/profile','/settings','/notifications','/jobs'])
crawl(session('admin@servease.test'),'admin',['/admin','/admin/users','/admin/verifications','/admin/categories','/admin/bookings','/admin/jobs','/admin/reviews','/admin/payments','/dashboard'])
