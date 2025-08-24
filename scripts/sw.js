/* Dynamic Static: data cache service worker */
const CACHE_NAME = 'ds-data-v1', MANIFEST_URL = '/data/manifest.json';
self.addEventListener('install', e => e.waitUntil((async ()=>{ try{
  const res=await fetch(MANIFEST_URL,{cache:'no-store'}), manifest=await res.json();
  const urls=Object.keys(manifest.files); const cache=await caches.open(CACHE_NAME);
  await cache.addAll([...urls, MANIFEST_URL]); self.skipWaiting();
}catch(e){ console.warn('[sw] manifest fetch failed', e);} })()));
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));
self.addEventListener('fetch', e => {
  const u=new URL(e.request.url);
  if(u.pathname.startsWith('/data/') && u.pathname.endsWith('.json')){
    e.respondWith((async ()=>{
      const c=await caches.open(CACHE_NAME), cached=await c.match(e.request);
      const net=(async()=>{ try{ const f=await fetch(e.request,{cache:'no-store'}); if(f.ok) await c.put(e.request,f.clone()); return f;}catch{ return cached||Response.error(); }})();
      return cached || net;
    })());
  }
});
