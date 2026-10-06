<?php

namespace App\Http\Controllers;

use App\Support\SeoSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        return response(SeoSettings::robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function sitemap(): Response
    {
        if (! SeoSettings::available()) {
            return response(
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>',
                200,
                [
                    'Content-Type' => 'application/xml; charset=UTF-8',
                    'Cache-Control' => 'public, max-age=3600',
                ]
            );
        }

        $urls = SeoSettings::sitemapUrls();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($u['loc'])."</loc>\n";
            $xml .= '    <changefreq>'.e($u['changefreq'])."</changefreq>\n";
            $xml .= '    <priority>'.e($u['priority'])."</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function downloadApex(Request $request)
    {
        abort_unless(SeoSettings::available(), 404);
        abort_unless($request->user()?->canAccess('settings'), 403);
        $html = SeoSettings::renderApexHtml();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="index.html"',
        ]);
    }

    public function publishApex(Request $request)
    {
        abort_unless(SeoSettings::available(), 404);
        abort_unless($request->user()?->canAccess('settings'), 403);
        $result = SeoSettings::publishApexLanding();
        $tab = (string) $request->input('settings_tab', 'seo');

        return redirect()
            ->route('settings.index', ['tab' => $tab])
            ->withFragment('seo')
            ->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('settings_tab', 'seo');
    }
}
