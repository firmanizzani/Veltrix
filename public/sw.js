const CACHE_NAME = 'veltrix-public-v1';
const OFFLINE_URL = '/offline.html';
const PUBLIC_FILES = [
    OFFLINE_URL,
    '/images/pwa-192.png',
    '/images/pwa-512.png',
    '/images/pwa-maskable-512.png',
    '/images/pwa-apple-180.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(PUBLIC_FILES)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(async (keys) => {
            await Promise.all(
                keys
                    .filter(
                        (key) => key.startsWith('veltrix-public-') && key !== CACHE_NAME,
                    )
                    .map((key) => caches.delete(key)),
            );
            await self.clients.claim();
        }),
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                const cache = await caches.open(CACHE_NAME);
                const offline = await cache.match(OFFLINE_URL);
                return offline ?? Response.error();
            }),
        );
        return;
    }

    if (!url.search && PUBLIC_FILES.includes(url.pathname)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async (cache) => {
                return (await cache.match(request)) ?? fetch(request);
            }),
        );
    }
});
