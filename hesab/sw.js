const CACHE = 'hesab-m-v1';
const BASE = self.registration.scope.replace(/\/$/, '');
const ASSETS = [
  BASE + '/m',
  BASE + '/assets/css/mobile.css?v=1',
  BASE + '/assets/js/mobile.js?v=1',
  BASE + '/manifest.webmanifest'
];
self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim())
  );
});
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  event.respondWith(
    caches.match(req).then((hit) => hit || fetch(req).catch(() => caches.match(BASE + '/m')))
  );
});
