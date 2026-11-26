import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../public/sw.js', import.meta.url), 'utf8');
const origin = 'https://veltrix.example';

function worker() {
    const listeners = new Map();
    const stores = new Map([['another-app-cache', new Map()]]);
    const state = {
        skipWaiting: 0,
        claimed: false,
        network: async () => new Response('live'),
    };
    const key = (request) =>
        new URL(typeof request === 'string' ? request : request.url, origin).pathname;
    runInNewContext(source, {
        URL,
        Response,
        fetch: (request) => state.network(request),
        caches: {
            keys: async () => [...stores.keys()],
            delete: async (name) => stores.delete(name),
            open: async (name) => {
                if (!stores.has(name)) {
                    stores.set(name, new Map());
                }
                const store = stores.get(name);
                return {
                    addAll: async (urls) =>
                        urls.forEach((url) => store.set(url, new Response(url))),
                    match: async (request) => store.get(key(request))?.clone(),
                };
            },
        },
        self: {
            location: { origin },
            addEventListener: (name, listener) => listeners.set(name, listener),
            clients: {
                claim: async () => {
                    state.claimed = true;
                },
            },
            skipWaiting: () => {
                state.skipWaiting++;
            },
        },
    });
    async function lifecycle(name) {
        let pending;
        listeners.get(name)({
            waitUntil: (promise) => {
                pending = promise;
            },
        });
        await pending;
    }
    async function fetchResource(path, options = {}) {
        let response;
        listeners.get('fetch')({
            request: {
                url: new URL(path, origin).href,
                method: 'GET',
                mode: 'cors',
                ...options,
            },
            respondWith: (promise) => {
                response = promise;
            },
        });
        return response;
    }
    return { listeners, stores, state, lifecycle, fetchResource };
}

test('installation caches only the offline page and public application icons', async () => {
    const app = worker();
    await app.lifecycle('install');
    const files = [...app.stores.get('veltrix-public-v1').keys()];
    assert.equal(files.length, 5);
    assert.ok(files.includes('/offline.html'));
    assert.ok(
        files.every(
            (file) => file === '/offline.html' || file.startsWith('/images/pwa-'),
        ),
    );
    assert.equal(app.state.skipWaiting, 0);
});

test('authenticated documents always come from the network and are never cached', async () => {
    const app = worker();
    await app.lifecycle('install');
    app.state.network = async () => new Response('private account and transaction data');
    const response = await app.fetchResource('/dashboard', { mode: 'navigate' });
    assert.equal(await response.text(), 'private account and transaction data');
    assert.equal(app.stores.get('veltrix-public-v1').has('/dashboard'), false);
});

test('a failed document navigation returns the public offline page', async () => {
    const app = worker();
    await app.lifecycle('install');
    app.state.network = async () => {
        throw new TypeError('offline');
    };
    const response = await app.fetchResource('/orders/123', { mode: 'navigate' });
    assert.equal(await response.text(), '/offline.html');
});

test('checkout mutations, Inertia data, downloads and other origins bypass the worker', async () => {
    const app = worker();
    for (const [path, options] of [
        ['/pos/checkout', { method: 'POST' }],
        ['/manage/inventory', {}],
        ['/reports/export', {}],
        ['/profile', { method: 'PUT' }],
        ['https://other.example/images/pwa-192.png', {}],
    ]) {
        assert.equal(await app.fetchResource(path, options), undefined);
    }
});

test('updates wait for explicit activation and only remove this applications old caches', async () => {
    const app = worker();
    await app.lifecycle('install');
    app.stores.set('veltrix-public-old', new Map());
    await app.lifecycle('activate');
    assert.equal(app.stores.has('another-app-cache'), true);
    assert.equal(app.stores.has('veltrix-public-old'), false);
    assert.equal(app.state.claimed, true);
    assert.equal(app.state.skipWaiting, 0);
    app.listeners.get('message')({ data: { type: 'SKIP_WAITING' } });
    assert.equal(app.state.skipWaiting, 1);
});

test('the manifest has real PNG icons at the advertised sizes', () => {
    const manifest = JSON.parse(
        readFileSync(new URL('../public/manifest.webmanifest', import.meta.url), 'utf8'),
    );
    assert.equal(manifest.display, 'standalone');
    assert.equal(manifest.start_url, '/dashboard');
    for (const icon of manifest.icons) {
        const png = readFileSync(new URL(`../public${icon.src}`, import.meta.url));
        assert.equal(png.subarray(1, 4).toString(), 'PNG');
        assert.equal(`${png.readUInt32BE(16)}x${png.readUInt32BE(20)}`, icon.sizes);
    }
    assert.ok(manifest.icons.some((icon) => icon.purpose === 'maskable'));
});
