"""Real-browser tests (Playwright) for the interactive forms: tiles, quick picks, live preview, save bar.
Run against a freshly installed demo database:  php database/install.php && python3 tests/test_browser.py
"""
import os, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
B = os.environ.get('SERVEASE_URL', 'http://127.0.0.1:8000').rstrip('/')
MAIL_LOG = ROOT / 'storage' / 'logs' / 'mail.log'
DB_HELPER = str(ROOT / 'tests' / 'db.php')
import asyncio, json, subprocess, time
from playwright.async_api import async_playwright
def q(sql): return json.loads(subprocess.check_output(['php',DB_HELPER,sql]))
res=[]
def check(c,m): res.append(('✓' if c else '✗')+' '+m)
async def login(pg,email,pw='servease123'):
    await pg.goto(B+'/login'); await pg.fill('#email',email); await pg.fill('#password',pw); await pg.click('#login-form button[type=submit]'); await pg.wait_for_load_state()
async def main():
    async with async_playwright() as p:
        b=await p.chromium.launch()
        errs=[]
        # 1 register a pro through the UI
        ctx=await b.new_context(viewport={'width':1280,'height':900}); pg=await ctx.new_page(); pg.on('pageerror',lambda e: errs.append(str(e)))
        await pg.goto(B+'/register')
        await pg.click('.role-option:has-text("Service provider")'); await pg.wait_for_load_state()
        await pg.fill('#first_name','Rico'); await pg.fill('#last_name','Tan'); await pg.fill('#email','rico@example.com')
        await pg.select_option('#city','Pasig'); await pg.click('.tile:has-text("Electrical")')
        await pg.fill('#password','sparky2026'); await pg.fill('#password_confirmation','sparky2026')
        rules=await pg.eval_on_selector_all('#pw-rules li.ok','e=>e.length'); check(rules==3,'password checklist ticks all 3 rules')
        check('match' in (await pg.inner_text('[data-match-msg]')),'confirm-password match message')
        await pg.click('label.check'); await pg.click('button:has-text("Create account")'); await pg.wait_for_load_state()
        r=q("SELECT u.role, c.slug FROM users u JOIN provider_profiles pp ON pp.user_id=u.id JOIN categories c ON c.id=pp.category_id WHERE email='rico@example.com'")
        check(r and r[0]['slug']=='electrical','pro registered via UI with Electrical tile')
        await ctx.close()
        # 2 client: post job via UI, book via UI, review via UI
        ctx=await b.new_context(viewport={'width':1280,'height':900}); pg=await ctx.new_page(); pg.on('pageerror',lambda e: errs.append(str(e)))
        await login(pg,'client@servease.test')
        await pg.goto(B+'/jobs/new'); await pg.click('.tile:has-text("Painting")')
        await pg.fill('#title','Repaint two bedroom walls'); await pg.fill('#description','Two walls about 3x2.7m each, light blue to white, minor cracks to patch.')
        await pg.click('button.pick:has-text("Next week")'); await pg.click('.choice:has-text("Urgent")'); await pg.click('button[data-budget="3000,8000"]')
        prev=await pg.inner_text('.preview-card'); check('Repaint two bedroom walls' in prev and '₱3,000' in prev and 'Urgent' in prev,'live preview mirrors title, budget, urgency')
        await pg.click('button:has-text("Post job")'); await pg.wait_for_load_state()
        j=q("SELECT urgency,budget_min,budget_max,preferred_date FROM jobs WHERE title='Repaint two bedroom walls'")
        check(j and j[0]['urgency']=='urgent' and float(j[0]['budget_min'])==3000 and j[0]['preferred_date'] is not None,'job saved with picks (urgent, ₱3k–8k, date)')
        await pg.goto(B+'/providers/3/book'); await pg.click('.choice:has-text("Leak repair")')
        check((await pg.input_value('#title'))=='Leak repair','service card fills title')
        await pg.fill('#details','Kitchen faucet drips all night long.'); await pg.click('.date-cell:not([disabled]) >> nth=4'); await pg.click('.pick:has(input:not(:disabled)):has-text("10:00 AM")'); await pg.fill('#address','12 Mabini St, Brgy. Pinyahan')
        await pg.click('button:has-text("Send request")'); await pg.wait_for_load_state()
        bk=q("SELECT scheduled_time, service_id FROM bookings WHERE details='Kitchen faucet drips all night long.'")
        check(bk and bk[0]['scheduled_time']=='10:00' and bk[0]['service_id'],'booking saved with time chip + service')
        await pg.goto(B+'/bookings/107'); await pg.click('label[for=r4]'); await pg.click('[data-review-tag="On time"]'); await pg.click('[data-review-tag="Fair price"]')
        check((await pg.inner_text('[data-rate-word]'))=='Great','rating word updates')
        await pg.click('button:has-text("Post review")'); await pg.wait_for_load_state()
        rv=q("SELECT rating, comment FROM reviews WHERE booking_id=107")
        check(rv and rv[0]['rating']==4 and 'On time' in rv[0]['comment'],'review saved with tag chips')
        # settings savebar appears only after edit
        await pg.goto(B+'/settings'); vis=await pg.eval_on_selector('.savebar','e=>e.classList.contains("is-visible")')
        await pg.fill('#first_name','Mariah'); vis2=await pg.eval_on_selector('.savebar','e=>e.classList.contains("is-visible")')
        check(not vis and vis2,'save bar hidden until something changes')
        await pg.click('.savebar .btn-primary'); await pg.wait_for_load_state()
        check(q("SELECT first_name FROM users WHERE email='client@servease.test'")[0]['first_name']=='Mariah','save bar button saves')
        await ctx.close()
        # 3 pro: quote via UI
        ctx=await b.new_context(viewport={'width':1280,'height':900}); pg=await ctx.new_page(); pg.on('pageerror',lambda e: errs.append(str(e)))
        await login(pg,'mark.aquino@servease.test')
        jid=q("SELECT id FROM jobs WHERE title='Repaint two bedroom walls'")[0]['id']
        await pg.goto(f'{B}/jobs/{jid}'); await pg.click('button.pick:has-text("₱8,000")'); await pg.click('button.pick:has-text("2 days")'); await pg.click('button:has-text("Use a template")')
        await pg.click('button:has-text("Send quote")'); await pg.wait_for_load_state()
        qt=q(f"SELECT amount, days_needed FROM quotes WHERE job_id={jid}")
        check(qt and float(qt[0]['amount'])==8000 and qt[0]['days_needed']==2,'quote sent via chips + template')
        await ctx.close()
        check(not errs, f'no JavaScript errors {errs[:3]}')
        await b.close()
    print('\n'.join(res)); print(sum(r.startswith('✓') for r in res),'passed,',sum(r.startswith('✗') for r in res),'failed')
asyncio.run(main())
