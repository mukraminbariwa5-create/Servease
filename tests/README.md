# Tests

Five suites run against a local copy of Servease with fresh demo data.

```bash
php database/install.php                      # reset demo data
php -S 127.0.0.1:8000 app/dev-router.php &    # or set SERVEASE_URL=http://localhost/Servease
pip install requests playwright && playwright install chromium

python3 tests/test_crawl.py     # ~550 pages as guest, client, pro and admin: no PHP errors or 5xx
python3 tests/test_flows.py     # ~110 end-to-end checks: flows, scheduling, reschedule, reports, reminders, permissions, payments, security
python3 tests/test_browser.py   # 13 real-browser checks of the interactive forms
python3 tests/test_brand.py     # brand colours: design tokens namespaced --sv-*, no live SVG filters, and the yellow logo and price tags stay yellow even with a hostile browser-extension stylesheet injected (phone and laptop, light and dark)
python3 tests/test_ui.py        # 85 phone and laptop checks: installable app, app bar, login-first entry, tab bar, banner, categories, services, payment screen, overflow, dark mode
```

What `test_flows.py` covers:
- Login-first: signed-out visitors start at the intro, returning ones at the login screen
- Registration, email verification, duplicate/weak-password rejection
- Pro profile, services, portfolio upload (rejects disguised files), private ID verification
- Job → quote → accept → booking, with ownership checks (other users can't accept or view)
- Working hours, job lengths and days off: only open days and times where the whole job fits can be booked, overlaps are rejected, and prices follow the service type
- Booking lifecycle, payment recording and confirmation, one review per completed booking, rating recalculation
- Messaging with HTML escaping (no XSS) and privacy
- Settings, password change and single-use reset links, CSRF protection, login throttling
- Admin: suspend/reactivate users, categories, review moderation, admin-only access
