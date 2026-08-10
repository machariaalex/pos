// App-shell caching only. This does NOT make sales/inventory work offline —
// every Livewire request still needs a live connection, and this worker never
// caches or replays them. It only keeps static assets (fonts/CSS/JS/logo) and
// a friendly offline fallback page available if the connection drops.
const CACHE_VERSION = 'v1';
const SHELL_CACHE = `waingo-shell-${CACHE_VERSION}`;

const PRECACHE_URLS = [
    '/offline.html',
    '/images/waingo.png',
    '/images/icons/icon-192.png',
    '/images/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== SHELL_CACHE).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return url.origin === self.location.origin
        && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/'));
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return; // Never intercept POST/PUT/etc — all Livewire traffic passes straight through.
    }

    const url = new URL(request.url);

    // Full-page navigations: go to the network, fall back to the offline
    // page only if the request truly fails (no connection).
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
        return;
    }

    // Hashed build assets and static images: cache-first, refreshing the
    // cache in the background so a later deploy's new asset still lands.
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request).then((response) => {
                    if (response.ok) {
                        caches.open(SHELL_CACHE).then((cache) => cache.put(request, response.clone()));
                    }
                    return response;
                }).catch(() => cached);

                return cached || network;
            })
        );
        return;
    }

    // Everything else (Livewire's own GETs, dynamic pages) — always network,
    // never cached, so nobody ever sees stale stock/cart/sales data.
});
