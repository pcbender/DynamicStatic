// Basic service worker for caching JSON data files with a stale-while-revalidate strategy.
// Caches files listed in data/manifest.json and keeps cache versioned by manifest.version.

const MANIFEST_URL = 'data/manifest.json';
const META_CACHE = 'meta-cache'; // stores the last manifest JSON

async function fetchManifest() {
  try {
    const res = await fetch(MANIFEST_URL, { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return await res.json();
  } catch (e) {
    console.warn('[sw] Manifest fetch failed', e);
    return null;
  }
}

async function cacheDataFiles(manifest) {
  if (!manifest) return;
  const version = manifest.version || 'v0';
  const cacheName = 'data-cache-' + version.slice(0, 16);
  const cache = await caches.open(cacheName);
  const fileList = Object.keys(manifest.files || {});
  await Promise.all(fileList.map(async f => {
    try {
      const res = await fetch('data/' + f, { cache: 'no-store' });
      if (res.ok) await cache.put('data/' + f, res.clone());
    } catch (e) {
      console.warn('[sw] Failed caching', f, e);
    }
  }));

  // Clean up old data caches
  const keys = await caches.keys();
  await Promise.all(keys.filter(k => k.startsWith('data-cache-') && k !== cacheName).map(k => caches.delete(k)));

  // Store manifest copy for later reference
  const metaCache = await caches.open(META_CACHE);
  await metaCache.put(MANIFEST_URL, new Response(JSON.stringify(manifest), { headers: { 'Content-Type': 'application/json' } }));
}

self.addEventListener('install', event => {
  event.waitUntil((async () => {
    const manifest = await fetchManifest();
    await cacheDataFiles(manifest);
  })());
});

self.addEventListener('activate', event => {
  event.waitUntil(self.clients.claim());
});

// Stale-while-revalidate for data JSON requests
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if (url.origin === self.location.origin && url.pathname.startsWith('/data/') && url.pathname.endsWith('.json')) {
    event.respondWith((async () => {
      const cacheKeys = await caches.keys();
      const dataCacheKey = cacheKeys.find(k => k.startsWith('data-cache-'));
      const dataCache = dataCacheKey ? await caches.open(dataCacheKey) : null;
      const cached = dataCache ? await dataCache.match(url.pathname.slice(1)) : null; // remove leading /
      const fetchPromise = (async () => {
        try {
          const res = await fetch(event.request, { cache: 'no-store' });
          if (res.ok && dataCache) await dataCache.put(url.pathname.slice(1), res.clone());
          // Check if manifest changed
          const manifest = await fetchManifest();
          if (manifest) await cacheDataFiles(manifest);
          return res;
        } catch (e) {
          if (cached) return cached; // offline fallback
          throw e;
        }
      })();
      return cached || fetchPromise;
    })());
  }
});
