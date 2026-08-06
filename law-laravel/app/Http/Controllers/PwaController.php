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
        $path = public_path('sw.js');
        $body = is_file($path) ? file_get_contents($path) : '';

        return response($body, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
