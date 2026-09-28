# Tests

Three suites run against a local copy of Fixoria with fresh demo data.

```bash
php database/install.php                      # reset demo data
php -S 127.0.0.1:8000 app/dev-router.php &    # or set FIXORIA_URL=http://localhost/Fixoria
pip install requests playwright && playwright install chromium

python3 tests/test_crawl.py     # ~550 pages as guest, client, pro and admin: no PHP errors or 5xx
python3 tests/test_flows.py     # 59 end-to-end checks: flows, permissions, payments, security
python3 tests/test_browser.py   # 13 real-browser checks of the interactive forms
```

What `test_flows.py` covers:
- Registration, email verification, duplicate/weak-password rejection
- Pro profile, services, portfolio upload (rejects disguised files), private ID verification
- Job → quote → accept → booking, with ownership checks (other users can't accept or view)
- Booking lifecycle, payment recording and confirmation, one review per completed booking, rating recalculation
- Messaging with HTML escaping (no XSS) and privacy
- Settings, password change and single-use reset links, CSRF protection, login throttling
- Admin: suspend/reactivate users, categories, review moderation, admin-only access
