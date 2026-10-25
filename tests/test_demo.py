"""Public demo mode: protected demo accounts, uploads off, daily reset.
Run with demo mode on, against a freshly installed demo database:
  config/config.php  ->  <?php return ['app' => ['public_demo' => true]];
  php database/install.php && python3 tests/test_demo.py
"""
import os, pathlib, re, json, subprocess, datetime
import requests

ROOT = pathlib.Path(__file__).resolve().parent.parent
B = os.environ.get('SERVEASE_URL', 'http://127.0.0.1:8000').rstrip('/')
DB_HELPER = str(ROOT / 'tests' / 'db.php')
STAMP = ROOT / 'storage' / 'cache' / 'demo-reset.txt'
ok = 0; fail = 0

def check(cond, msg):
    global ok, fail
    if cond: ok += 1; print('  ✓', msg)
    else: fail += 1; print('  ✗ FAIL:', msg)
def q(sql): return json.loads(subprocess.check_output(['php', DB_HELPER, sql]))
def tok(s, path='/'):
    r = s.get(B + path)
    m = re.search(r'name="csrf-token" content="([^"]+)"', r.text) or re.search(r'name="_token" value="([^"]+)"', r.text)
    return m.group(1)
def post(s, path, data=None, files=None):
    data = dict(data or {}); data['_token'] = tok(s)
    return s.post(B + path, data=data, files=files, allow_redirects=True)
def login(email, pw='servease123'):
    s = requests.Session(); r = post(s, '/login', {'email': email, 'password': pw}); return s, r
def signed_in(r): return '/login' not in r.url and '/welcome' not in r.url

# Mark today as already reset, so the checks below run on today's data without a surprise reset.
STAMP.parent.mkdir(parents=True, exist_ok=True)
STAMP.write_text(datetime.date.today().isoformat())

print('1. Demo notice')
r = requests.get(B + '/login')
check('public demo that resets every day' in r.text, 'sign-in page says it is a public demo')

print('2. Demo accounts are protected')
c, r = login('client@servease.test')
check(signed_in(r), 'demo customer signs in')
hash_before = q("SELECT password_hash FROM users WHERE email='client@servease.test'")[0]['password_hash']
r = post(c, '/settings/password', {'current_password': 'servease123', 'password': 'hijacked99', 'password_confirmation': 'hijacked99'})
check(q("SELECT password_hash FROM users WHERE email='client@servease.test'")[0]['password_hash'] == hash_before, 'demo password change refused')
check('shared demo account' in r.text, 'visitor sees why')
r = post(c, '/settings', {'first_name': 'Hacked', 'last_name': 'Name', 'email': 'evil@example.com', 'phone': '', 'city': ''})
row = q("SELECT first_name, email FROM users WHERE email='client@servease.test'")
check(len(row) == 1 and row[0]['first_name'] == 'Maria', 'demo details change refused (name and email kept)')
_, r = login('client@servease.test')
check(signed_in(r), 'the next visitor can still sign in with the demo password')

post(requests.Session(), '/forgot-password', {'email': 'client@servease.test'})
check(q("SELECT reset_token_hash FROM users WHERE email='client@servease.test'")[0]['reset_token_hash'] is None, 'no password-reset link is made for a demo account')

a, _ = login('admin@servease.test')
victim = q("SELECT id FROM users WHERE email='provider@servease.test'")[0]['id']
r = post(a, f'/admin/users/{victim}/status')
check(q(f"SELECT status FROM users WHERE id={victim}")[0]['status'] == 'active', 'admin cannot suspend a demo account')

print('3. Visitors\' own accounts still work normally')
n = requests.Session()
r = post(n, '/register', {'role': 'client', 'first_name': 'Visitor', 'last_name': 'One', 'email': 'visitor@example.com', 'phone': '09171234567',
                          'city': 'Pasig', 'password': 'secret123', 'password_confirmation': 'secret123', 'terms': '1'})
check(q("SELECT COUNT(*) n FROM users WHERE email='visitor@example.com'")[0]['n'] in (1, '1'), 'visitor creates their own account')
r = post(n, '/settings/password', {'current_password': 'secret123', 'password': 'newsecret123', 'password_confirmation': 'newsecret123'})
_, r = login('visitor@example.com', 'newsecret123')
check(signed_in(r), 'visitor can change their own password')
vid = q("SELECT id FROM users WHERE email='visitor@example.com'")[0]['id']
r = post(a, f'/admin/users/{vid}/status')
check(q(f"SELECT status FROM users WHERE id={vid}")[0]['status'] == 'suspended', 'admin can still suspend a visitor account')

print('4. Uploads are off')
avatars_before = set(os.listdir(ROOT / 'uploads' / 'avatars'))
r = post(c, '/settings/avatar', {}, files={'avatar': ('me.jpg', open(ROOT / 'tests' / 'fixtures' / 'photo.jpg', 'rb'), 'image/jpeg')})
check(set(os.listdir(ROOT / 'uploads' / 'avatars')) == avatars_before, 'no photo saved')
p, _ = login('provider@servease.test')
pid = q("SELECT id FROM users WHERE email='provider@servease.test'")[0]['id']
before = q(f"SELECT COUNT(*) n FROM portfolio_items WHERE provider_id={pid}")[0]['n']
r = post(p, '/provider/portfolio', {'caption': 'x'}, files={'image': ('a.jpg', open(ROOT / 'tests' / 'fixtures' / 'photo.jpg', 'rb'), 'image/jpeg')})
check(q(f"SELECT COUNT(*) n FROM portfolio_items WHERE provider_id={pid}")[0]['n'] == before, 'portfolio upload refused')
check('Uploads are turned off in the public demo' in r.text, 'visitor sees why')

print('5. Daily reset')
STAMP.write_text('2000-01-01')
r = requests.get(B + '/login')
check(STAMP.read_text().strip() == datetime.date.today().isoformat(), 'first visit of a new day runs the reset')
check(q("SELECT COUNT(*) n FROM users WHERE email='visitor@example.com'")[0]['n'] in (0, '0'), 'visitor accounts are gone after the reset')
check(q(f"SELECT status FROM users WHERE email='provider@servease.test'")[0]['status'] == 'active', 'demo accounts are back to normal')
_, r = login('client@servease.test')
check(signed_in(r), 'demo sign-in works after the reset')
n_users = q("SELECT COUNT(*) n FROM users")[0]['n']
requests.get(B + '/login')
check(q("SELECT COUNT(*) n FROM users")[0]['n'] == n_users, 'later visits the same day do not reset again')

print(f'\n{ok} passed, {fail} failed')
raise SystemExit(1 if fail else 0)
