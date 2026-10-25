"""Brand colour guard: the yellow logo tag and yellow price tags must stay yellow on every screen.

1. Code checks
   - Every design token on :root is namespaced --sv-*. Browser extensions inject their own
     stylesheets with generic names; one (seen in Avast Secure Browser) sets
     :root { --accent: #0F71F0 }, which turned every yellow thing blue whenever it loaded last.
   - No live SVG filters (filter: url(#...), <filter>): photo tints are baked into files
     (stock_photo() in app/helpers.php) so a filter can never be painted onto the wrong element.
2. Browser check: on a laptop and a phone, light and dark, with a hostile "extension" stylesheet
   injected last into every page (generic --accent/--primary/--text/... set to wrong colours),
   click through the app like a person does and measure the real screen pixels of the logo and
   the home price tag.

Run against a local copy:  python3 tests/test_brand.py   (SERVEASE_URL=http://localhost/Servease to point elsewhere)
"""
import os, re, io, glob, asyncio
from playwright.async_api import async_playwright
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
B = os.environ.get('SERVEASE_URL', 'http://127.0.0.1:8000').rstrip('/')
res = []
def check(c, m): res.append(('✓' if c else '✗') + ' ' + m)

# 1. Code check ------------------------------------------------------------
LIVE_FILTER = re.compile(r'filter\s*[:=]\s*["\']?\s*url\(|<filter[\s>]', re.I)
files = glob.glob(ROOT + '/assets/css/*.css') + glob.glob(ROOT + '/assets/js/*.js') + glob.glob(ROOT + '/app/Views/**/*.php', recursive=True) + [ROOT + '/app/icons.php']
hits = []
for f in files:
    for n, line in enumerate(open(f, encoding='utf-8', errors='ignore'), 1):
        if LIVE_FILTER.search(line) and not line.lstrip().startswith(('/*', '*', '//')):
            hits.append(f'{os.path.relpath(f, ROOT)}:{n}')
css = open(ROOT + '/assets/css/app.css', encoding='utf-8').read()
root_blocks = re.findall(r'(?:^|\n)\s*(?::root|\[data-theme="dark"\])\s*\{([^}]*)\}', css)
bare = sorted({n for b in root_blocks for n in re.findall(r'(--[\w-]+)\s*:', b) if not n.startswith('--sv-')})
check(root_blocks and not bare, 'all :root design tokens are namespaced --sv-' + (f' (not namespaced: {", ".join(bare)})' if bare else ''))
check(not hits, 'no live SVG filters in styles, views or scripts' + (f' (found: {", ".join(hits)})' if hits else ''))
missing = [p for p in glob.glob(ROOT + '/assets/img/photos/*.jpg') if not os.path.isfile(ROOT + '/assets/img/photos/duo/' + os.path.basename(p))]
check(not missing, 'every trade photo has its baked duotone copy' + (f' (missing: {missing})' if missing else ' (made on first view if a new photo is added)'))

# 2. Browser check ---------------------------------------------------------
def yellow_share(png, box):
    """Share of pixels inside box that are the brand yellow (#ffc61a, allowing antialiasing)."""
    img = Image.open(io.BytesIO(png)).convert('RGB')
    s = img.width / VIEW_W
    x0, y0, x1, y1 = [int(v * s) for v in box]
    raw = img.crop((x0, y0, x1, y1)).tobytes()
    px = [raw[i:i + 3] for i in range(0, len(raw), 3)]
    yellow = sum(1 for r, g, b in px if r > 225 and 165 < g < 225 and b < 90)
    return yellow / max(1, len(px))

async def measure(pg, where):
    await pg.wait_for_timeout(700)  # let page transitions finish
    boxes = await pg.evaluate("""() => {
        const r = s => { const e = document.querySelector(s); if (!e || getComputedStyle(e).visibility === 'hidden') return null;
                         const b = e.getBoundingClientRect(); return b.width && b.bottom > 0 && b.top < innerHeight ? [b.left, b.top, b.right, b.bottom] : null; };
        return { logo: r('.topbar .brand .logo'), tag: r('.banner-tag') };
    }""")
    fill = await pg.evaluate("() => { const t = document.querySelector('.topbar .logo-tag'); return t ? getComputedStyle(t).fill : null; }")
    png = await pg.screenshot()
    if boxes['logo']:
        share = yellow_share(png, boxes['logo'])
        check(fill == 'rgb(255, 198, 26)' and share > 0.06, f'{where}: logo tag is yellow (fill {fill}, {share:.0%} yellow pixels)')
    if boxes['tag']:
        share = yellow_share(png, boxes['tag'])
        check(share > 0.45, f'{where}: home price tag is yellow ({share:.0%} yellow pixels)')
    stock = await pg.evaluate("() => [...document.querySelectorAll('img.is-stock')].filter(i => getComputedStyle(i).filter !== 'none').length")
    check(stock == 0, f'{where}: trade photos carry no live filter')

# What a browser extension does: append its own stylesheet with generic token names, after the page's CSS
HOSTILE = """
document.addEventListener('DOMContentLoaded', () => {
  const st = document.createElement('style');
  st.textContent = ':root{--accent:#0F71F0;--accent-soft:#0F71F0;--on-accent:#fff;--primary:#e11d48;--text:#e11d48;--bg:#000;--surface:#000;--border:#e11d48;--radius:0;--font:serif}';
  document.head.appendChild(st);
});
"""

VIEW_W = 1280
async def main():
    global VIEW_W
    async with async_playwright() as p:
        b = await p.chromium.launch()
        devices = [('laptop', dict(viewport={'width': 1280, 'height': 860})),
                   ('phone', dict(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True, device_scale_factor=2))]
        for name, opts in devices:
            for scheme in ('light', 'dark'):
                VIEW_W = opts['viewport']['width']
                ctx = await b.new_context(color_scheme=scheme, **opts)
                await ctx.add_init_script(HOSTILE)
                pg = await ctx.new_page()
                await pg.goto(B + '/login'); await pg.fill('#email', 'client@servease.test'); await pg.fill('#password', 'servease123')
                await pg.click('#login-form button[type=submit]'); await pg.wait_for_load_state()
                nav = '.top-link' if name == 'laptop' else '.tab-item'
                tag = f'{name}/{scheme}'
                await pg.goto(B + '/'); await measure(pg, f'{tag} home')
                for label in ('Services', 'Bookings', 'Home'):
                    await pg.click(f'{nav}:has-text("{label}")'); await pg.wait_for_load_state()
                    await measure(pg, f'{tag} after clicking {label}')
                await pg.go_back(); await pg.wait_for_load_state(); await measure(pg, f'{tag} after Back')
                await pg.go_forward(); await pg.wait_for_load_state(); await measure(pg, f'{tag} after Forward to home')
                await ctx.close()
        await b.close()

asyncio.run(main())
print('\n'.join(res)); print(sum(r.startswith('✓') for r in res), 'passed,', sum(r.startswith('✗') for r in res), 'failed')
raise SystemExit(1 if any(r.startswith('✗') for r in res) else 0)
