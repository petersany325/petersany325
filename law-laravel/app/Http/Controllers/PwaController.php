<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $name = Setting::get('pwa_name') ?: (Setting::get('site_name') ?: 'مؤسسه حقوقی آریان');
        $short = Setting::get('pwa_short_name') ?: 'آریان';
        $description = Setting::get('pwa_description') ?: (Setting::get('hero_lead') ?: 'وکالت و مشاوره حقوقی تخصصی');
        $theme = Setting::get('pwa_theme_color') ?: '#0a1628';
        $bg = Setting::get('pwa_bg_color') ?: '#0a1628';
        $start = Setting::get('pwa_start_url') ?: '/app';

        return response()->json([
            'id' => url('/app'),
            'name' => $name,
            'short_name' => $short,
            'description' => $description,
            'lang' => 'fa',
            'dir' => 'rtl',
            'start_url' => url($start).'?source=pwa',
            'scope' => url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => $bg,
            'theme_color' => $theme,
            'categories' => ['business', 'lifestyle'],
            'icons' => [
                [
                    'src' => asset('assets/icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/icons/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => Setting::get('cta_text') ?: 'درخواست نوبت',
                    'short_name' => 'نوبت',
                    'url' => url('/app#appointment'),
                    'icons' => [['src' => asset('assets/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name' => 'تماس',
                    'short_name' => 'تماس',
                    'url' => url('/app#contact'),
                    'icons' => [['src' => asset('assets/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function serviceWorker(): Response
    {
        $v = max(1, Setting::int('asset_version', 12));
        $cache = 'aryan-pwa-v'.$v;

        $body = <<<JS
/* Law firm PWA service worker (dynamic) */
const CACHE = '{$cache}';
const PRECACHE = [
  '/app',
  '/assets/css/style.css?v={$v}',
  '/assets/css/app.css?v={$v}',
  '/assets/js/main.js?v={$v}',
  '/assets/js/app.js?v={$v}',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE).catch(() => undefined)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy));
          return res;
        })
        .catch(() => caches.match(req).then((cached) => cached || caches.match('/app')))
    );
    return;
  }

  event.respondWith(
    caches.match(req).then((cached) => {
      const network = fetch(req).then((res) => {
        if (res && res.status === 200) {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy));
        }
        return res;
      }).catch(() => cached);
      return cached || network;
    })
  );
});
JS;

        return response($body, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
