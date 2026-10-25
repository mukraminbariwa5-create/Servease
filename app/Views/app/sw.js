/* Servease service worker.
 * - Pages always come from the network (they're personal and change often); when there's
 *   no connection the offline screen is shown instead of the browser's error page.
 * - The app shell (styles, script, fonts, icons) and uploaded photos are served from the
 *   cache and refreshed in the background, so the installed app opens fast.
 * - Forms (POST) and anything from another site are never touched.
 */
const CACHE = '__CACHE__';
const BASE = '__BASE__';
const SHELL = __SHELL__;
const OFFLINE = BASE + '/offline';

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k.startsWith('servease-') && k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

const isAsset = url => url.pathname.startsWith(BASE + '/assets/') || url.pathname.startsWith(BASE + '/uploads/');

self.addEventListener('fetch', event => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin || !url.pathname.startsWith(BASE + '/')) return;

  // Pages: network first, offline screen when there's no connection
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        // One quick retry: right after the connection comes back the first request can still fail
        .catch(() => new Promise(r => setTimeout(r, 400)).then(() => fetch(req)))
        .catch(() => caches.match(OFFLINE, { ignoreSearch: true }).then(r => r || new Response('Offline', { status: 503 })))
    );
    return;
  }

  // Styles, scripts, fonts, images and photos: cache first, refresh in the background
  if (isAsset(url)) {
    event.respondWith(
      caches.open(CACHE).then(cache => cache.match(req).then(hit => {
        const fresh = fetch(req).then(res => {
          if (res.ok && res.type === 'basic') cache.put(req, res.clone());
          return res;
        }).catch(() => hit);
        return hit || fresh;
      }))
    );
  }
});

self.addEventListener('message', event => {
  if (event.data === 'skip-waiting') self.skipWaiting();
});
