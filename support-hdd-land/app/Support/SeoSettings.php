<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;

/**
 * Site-wide SEO engine: meta, Open Graph, JSON-LD, robots, sitemap, apex landing.
 */
class SeoSettings
{
    public const KEYS = [
        'seo_enabled' => '1',
        'seo_site_title' => '',
        'seo_title_suffix' => '',
        'seo_description' => '',
        'seo_keywords' => '',
        'seo_canonical_base' => '',
        'seo_locale' => 'fa_IR',
        'seo_robots_public' => 'index,follow',
        'seo_robots_private' => 'noindex,nofollow',
        'seo_index_gate' => '1',
        'seo_index_login' => '1',
        'seo_index_cartable' => '1',
        'seo_og_title' => '',
        'seo_og_description' => '',
        'seo_og_image' => '',
        'seo_twitter_card' => 'summary_large_image',
        'seo_gsc_verification' => '',
        'seo_bing_verification' => '',
        'seo_business_type' => 'LocalBusiness',
        'seo_business_name' => '',
        'seo_business_alt_name' => '',
        'seo_business_phone' => '',
        'seo_business_mobile' => '',
        'seo_business_email' => '',
        'seo_business_address' => '',
        'seo_business_city' => '',
        'seo_business_region' => 'مازندران',
        'seo_business_postal' => '',
        'seo_business_country' => 'IR',
        'seo_geo_lat' => '',
        'seo_geo_lng' => '',
        'seo_same_as' => '',
        'seo_price_range' => '$$',
        'seo_opening_hours' => '',
        'seo_robots_extra' => '',
        'seo_apex_enabled' => '1',
        'seo_apex_headline' => '',
        'seo_apex_lead' => '',
        'seo_apex_cta' => 'ورود به سامانه',
        'seo_apex_support_url' => '',
    ];

    /** @return array<string, string> */
    public static function all(): array
    {
        $out = [];
        foreach (self::KEYS as $key => $default) {
            try {
                $out[substr($key, 4)] = (string) (AppSetting::getValue($key, $default) ?? $default);
            } catch (\Throwable) {
                $out[substr($key, 4)] = $default;
            }
        }

        // Friendly defaults from shop brand when empty
        if (trim($out['site_title']) === '') {
            $out['site_title'] = shop_name();
        }
        if (trim($out['title_suffix']) === '') {
            $out['title_suffix'] = shop_tagline();
        }
        if (trim($out['description']) === '') {
            $out['description'] = 'سامانه پذیرش، پیگیری تعمیر و کارتابل مشتری — '.shop_name();
        }
        if (trim($out['business_name']) === '') {
            $out['business_name'] = shop_name();
        }
        if (trim($out['business_phone']) === '') {
            $out['business_phone'] = (string) (AppSetting::getValue('invoice_phones', shop_office_phone()) ?: shop_office_phone());
        }
        if (trim($out['business_address']) === '') {
            $out['business_address'] = (string) (AppSetting::getValue('invoice_address', '') ?? '');
        }
        if (trim($out['og_title']) === '') {
            $out['og_title'] = $out['site_title'];
        }
        if (trim($out['og_description']) === '') {
            $out['og_description'] = $out['description'];
        }
        if (trim($out['og_image']) === '') {
            $out['og_image'] = shop_logo_url('main');
        }
        if (trim($out['apex_headline']) === '') {
            $out['apex_headline'] = $out['business_name'];
        }
        if (trim($out['apex_lead']) === '') {
            $out['apex_lead'] = 'برای ثبت قبض، پیگیری تعمیر و کارتابل کارمندان وارد سامانه شوید.';
        }
        if (trim($out['apex_support_url']) === '') {
            $out['apex_support_url'] = url('/');
        }
        if (trim($out['canonical_base']) === '') {
            $out['canonical_base'] = rtrim((string) config('app.url', url('/')), '/');
        }

        return $out;
    }

    /** Seller marketing hub only — never enable SEO tooling on customer installs. */
    public static function available(): bool
    {
        try {
            return LicenseStatus::isSellerSite();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function enabled(): bool
    {
        if (! self::available()) {
            return false;
        }

        return (self::all()['enabled'] ?? '1') === '1';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data, ?UploadedFile $ogImage = null): void
    {
        if (! self::available()) {
            abort(404);
        }

        $boolKeys = [
            'enabled', 'index_gate', 'index_login', 'index_cartable', 'apex_enabled',
        ];
        foreach ($boolKeys as $k) {
            AppSetting::setValue('seo_'.$k, ! empty($data[$k]) ? '1' : '0');
        }

        $textKeys = [
            'site_title', 'title_suffix', 'description', 'keywords', 'canonical_base', 'locale',
            'robots_public', 'robots_private', 'og_title', 'og_description', 'twitter_card',
            'gsc_verification', 'bing_verification', 'business_type', 'business_name',
            'business_alt_name', 'business_phone', 'business_mobile', 'business_email',
            'business_address', 'business_city', 'business_region', 'business_postal',
            'business_country', 'geo_lat', 'geo_lng', 'same_as', 'price_range',
            'opening_hours', 'robots_extra', 'apex_headline', 'apex_lead', 'apex_cta',
            'apex_support_url',
        ];
        foreach ($textKeys as $k) {
            if (! array_key_exists($k, $data)) {
                continue;
            }
            $v = is_string($data[$k]) ? trim($data[$k]) : '';
            if ($k === 'canonical_base' || $k === 'apex_support_url') {
                $v = rtrim($v, '/');
            }
            if ($k === 'robots_public' && $v === '') {
                $v = 'index,follow';
            }
            if ($k === 'robots_private' && $v === '') {
                $v = 'noindex,nofollow';
            }
            if ($k === 'twitter_card' && ! in_array($v, ['summary', 'summary_large_image'], true)) {
                $v = 'summary_large_image';
            }
            if ($k === 'business_type' && $v === '') {
                $v = 'LocalBusiness';
            }
            if ($k === 'locale' && $v === '') {
                $v = 'fa_IR';
            }
            AppSetting::setValue('seo_'.$k, $v);
        }

        if ($ogImage) {
            $dir = public_path('images/seo');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $name = 'og-'.date('YmdHis').'.'.$ogImage->getClientOriginalExtension();
            $ogImage->move($dir, $name);
            $rel = 'images/seo/'.$name;
            AppSetting::setValue('seo_og_image', asset($rel));
            AppSetting::setValue('brand_logo_version', (string) time());
        }
    }

    public static function pageKind(?Request $request = null): string
    {
        $request = $request ?: request();
        $path = trim($request->path(), '/');
        if ($path === '' || $path === '/') {
            return 'gate';
        }
        if ($path === 'login' || str_starts_with($path, 'login/')) {
            return 'login';
        }
        if ($path === 'cartable' || str_starts_with($path, 'cartable/')) {
            // Only the public cartable login should be indexable; authenticated portal stays private.
            if ($path === 'cartable' && ! $request->user()) {
                return 'cartable';
            }

            return 'private';
        }

        return 'private';
    }

    public static function shouldIndex(?Request $request = null): bool
    {
        if (! self::available()) {
            return false;
        }
        $seo = self::all();
        if (($seo['enabled'] ?? '1') !== '1') {
            return false;
        }
        $kind = self::pageKind($request);

        return match ($kind) {
            'gate' => ($seo['index_gate'] ?? '1') === '1',
            'login' => ($seo['index_login'] ?? '1') === '1',
            'cartable' => ($seo['index_cartable'] ?? '1') === '1',
            default => false,
        };
    }

    public static function documentTitle(?string $pageTitle = null, ?Request $request = null): string
    {
        if (! self::available()) {
            if (is_string($pageTitle) && trim($pageTitle) !== '') {
                return trim($pageTitle);
            }

            return shop_name();
        }

        $seo = self::all();
        $kind = self::pageKind($request);
        if (in_array($kind, ['gate', 'login', 'cartable'], true) && trim((string) $seo['site_title']) !== '') {
            $base = trim((string) $seo['site_title']);
            $suffix = trim((string) $seo['title_suffix']);
            if ($kind === 'login') {
                return $suffix !== '' ? ($base.' | ورود کارمندان') : ($base.' | ورود');
            }
            if ($kind === 'cartable') {
                return $suffix !== '' ? ($base.' | کارتابل مشتری') : ($base.' | کارتابل');
            }

            return $suffix !== '' ? ($base.' | '.$suffix) : $base;
        }

        if (is_string($pageTitle) && trim($pageTitle) !== '') {
            return trim($pageTitle);
        }

        return shop_name();
    }

    public static function canonicalUrl(?Request $request = null): string
    {
        $seo = self::all();
        $base = rtrim((string) $seo['canonical_base'], '/');
        $request = $request ?: request();
        $path = '/'.ltrim($request->getPathInfo(), '/');
        if ($path === '//') {
            $path = '/';
        }
        // Prefer configured public base for SEO pages
        if ($base !== '') {
            return $base.($path === '/' ? '/' : rtrim($path, '/'));
        }

        return url($path === '/' ? '/' : $path);
    }

    /** @return array<string, mixed> */
    public static function metaPayload(?Request $request = null, ?string $pageTitle = null): array
    {
        if (! self::available()) {
            return [
                'enabled' => false,
                'title' => $pageTitle ?: shop_name(),
                'description' => '',
                'keywords' => '',
                'canonical' => url()->current(),
                'robots' => 'noindex,nofollow',
                'indexable' => false,
                'locale' => 'fa_IR',
                'og_title' => '',
                'og_description' => '',
                'og_image' => '',
                'og_url' => url()->current(),
                'og_type' => 'website',
                'twitter_card' => 'summary',
                'gsc_verification' => '',
                'bing_verification' => '',
                'json_ld' => null,
                'site_name' => shop_name(),
            ];
        }

        $seo = self::all();
        $index = self::shouldIndex($request);
        $title = self::documentTitle($pageTitle, $request);

        return [
            'enabled' => ($seo['enabled'] ?? '1') === '1',
            'title' => $title,
            'description' => (string) $seo['description'],
            'keywords' => (string) $seo['keywords'],
            'canonical' => self::canonicalUrl($request),
            'robots' => $index ? (string) $seo['robots_public'] : (string) $seo['robots_private'],
            'indexable' => $index,
            'locale' => (string) $seo['locale'],
            'og_title' => (string) ($seo['og_title'] ?: $title),
            'og_description' => (string) ($seo['og_description'] ?: $seo['description']),
            'og_image' => (string) $seo['og_image'],
            'og_url' => self::canonicalUrl($request),
            'og_type' => 'website',
            'twitter_card' => (string) $seo['twitter_card'],
            'gsc_verification' => (string) $seo['gsc_verification'],
            'bing_verification' => (string) $seo['bing_verification'],
            'json_ld' => $index ? self::jsonLd($seo) : null,
            'site_name' => (string) $seo['business_name'],
        ];
    }

    /**
     * @param  array<string, string>  $seo
     * @return array<string, mixed>
     */
    public static function jsonLd(array $seo): array
    {
        $url = rtrim((string) $seo['canonical_base'], '/') ?: url('/');
        $sameAs = preg_split('/\r\n|\r|\n|,/', (string) $seo['same_as']) ?: [];
        $sameAs = array_values(array_filter(array_map('trim', $sameAs)));

        $org = [
            '@context' => 'https://schema.org',
            '@type' => $seo['business_type'] !== '' ? $seo['business_type'] : 'LocalBusiness',
            'name' => $seo['business_name'] ?: shop_name(),
            'url' => $url,
            'description' => $seo['description'],
            'image' => $seo['og_image'] ?: shop_logo_url('main'),
            'priceRange' => $seo['price_range'] ?: '$$',
        ];
        if (trim((string) $seo['business_alt_name']) !== '') {
            $org['alternateName'] = $seo['business_alt_name'];
        }
        $phones = array_values(array_filter([
            trim((string) $seo['business_phone']),
            trim((string) $seo['business_mobile']),
        ]));
        if ($phones) {
            $org['telephone'] = count($phones) === 1 ? $phones[0] : $phones;
        }
        if (trim((string) $seo['business_email']) !== '') {
            $org['email'] = $seo['business_email'];
        }
        if (trim((string) $seo['business_address']) !== '') {
            $org['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $seo['business_address'],
                'addressLocality' => $seo['business_city'] ?: null,
                'addressRegion' => $seo['business_region'] ?: null,
                'postalCode' => $seo['business_postal'] ?: null,
                'addressCountry' => $seo['business_country'] ?: 'IR',
            ];
            $org['address'] = array_filter($org['address'], fn ($v) => $v !== null && $v !== '');
        }
        if (trim((string) $seo['geo_lat']) !== '' && trim((string) $seo['geo_lng']) !== '') {
            $org['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $seo['geo_lat'],
                'longitude' => $seo['geo_lng'],
            ];
        }
        if ($sameAs) {
            $org['sameAs'] = $sameAs;
        }
        if (trim((string) $seo['opening_hours']) !== '') {
            $hours = preg_split('/\r\n|\r|\n/', (string) $seo['opening_hours']) ?: [];
            $hours = array_values(array_filter(array_map('trim', $hours)));
            if ($hours) {
                $org['openingHours'] = count($hours) === 1 ? $hours[0] : $hours;
            }
        }

        $website = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $seo['business_name'] ?: shop_name(),
            'url' => $url,
            'inLanguage' => str_replace('_', '-', $seo['locale'] ?: 'fa-IR'),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $seo['business_name'] ?: shop_name(),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $seo['og_image'] ?: shop_logo_url('main'),
                ],
            ],
        ];

        return ['graph' => [$org, $website]];
    }

    public static function robotsTxt(): string
    {
        if (! self::available() || ! self::enabled()) {
            return "User-agent: *\nDisallow: /\n";
        }

        $seo = self::all();
        $base = rtrim((string) $seo['canonical_base'], '/') ?: url('/');
        $lines = [
            'User-agent: *',
            'Allow: /$',
            'Allow: /login$',
            'Allow: /cartable$',
            'Disallow: /cartable/',
            'Disallow: /dashboard',
            'Disallow: /receptions',
            'Disallow: /customers',
            'Disallow: /employees',
            'Disallow: /parts',
            'Disallow: /reports',
            'Disallow: /settings',
            'Disallow: /system-tools',
            'Disallow: /licenses',
            'Disallow: /attendance',
            'Disallow: /a/',
            'Disallow: /cron/',
            'Disallow: /license/',
            'Disallow: /install.php',
            'Disallow: /storage/',
            '',
            'Sitemap: '.$base.'/sitemap.xml',
        ];
        $extra = trim((string) $seo['robots_extra']);
        if ($extra !== '') {
            $lines[] = '';
            $lines[] = '# Custom rules';
            foreach (preg_split('/\r\n|\r|\n/', $extra) ?: [] as $row) {
                $row = trim($row);
                if ($row !== '') {
                    $lines[] = $row;
                }
            }
        }

        return implode("\n", $lines)."\n";
    }

    /** @return list<array{loc:string,changefreq:string,priority:string}> */
    public static function sitemapUrls(): array
    {
        if (! self::available() || ! self::enabled()) {
            return [];
        }

        $seo = self::all();
        $base = rtrim((string) $seo['canonical_base'], '/') ?: url('/');
        $urls = [];
        if (($seo['index_gate'] ?? '1') === '1') {
            $urls[] = ['loc' => $base.'/', 'changefreq' => 'weekly', 'priority' => '1.0'];
        }
        if (($seo['index_login'] ?? '1') === '1') {
            $urls[] = ['loc' => $base.'/login', 'changefreq' => 'monthly', 'priority' => '0.6'];
        }
        if (($seo['index_cartable'] ?? '1') === '1') {
            $urls[] = ['loc' => $base.'/cartable', 'changefreq' => 'monthly', 'priority' => '0.7'];
        }

        return $urls;
    }

    public static function renderApexHtml(): string
    {
        if (! self::available()) {
            abort(404);
        }

        $seo = self::all();
        $titleRaw = trim($seo['site_title'].(trim($seo['title_suffix']) !== '' ? ' | '.$seo['title_suffix'] : ''));
        if ($titleRaw === '') {
            $titleRaw = shop_name().' | ورود به سامانه';
        }
        $title = e($titleRaw);
        $desc = e($seo['description']);
        $name = e($seo['business_name'] ?: shop_name());
        $headline = e($seo['apex_headline'] ?: ($seo['business_name'] ?: shop_name()));
        $lead = e($seo['apex_lead']);
        $cta = e($seo['apex_cta'] ?: 'ورود به سامانه');
        $support = e($seo['apex_support_url'] ?: url('/'));
        $canonical = e(rtrim($seo['canonical_base'], '/') ?: url('/'));
        $ogTitle = e($seo['og_title'] ?: $titleRaw);
        $ogDesc = e($seo['og_description'] ?: $seo['description']);
        $ogImage = e($seo['og_image'] ?: shop_logo_url('main'));
        $phone = e($seo['business_phone']);
        $mobile = e($seo['business_mobile']);
        $keywords = e($seo['keywords']);
        $locale = e(str_replace('_', '-', $seo['locale'] ?: 'fa-IR'));
        $json = self::jsonLd($seo);
        $jsonLd = str_replace('</', '<\/', (string) json_encode($json['graph'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $contact = '';
        if ($phone !== '') {
            $contact .= 'پشتیبانی <a href="tel:'.e(preg_replace('/\s+/', '', $seo['business_phone'])).'" dir="ltr">'.$phone.'</a>';
        }
        if ($mobile !== '') {
            $contact .= ($contact !== '' ? ' · ' : '').'موبایل <a href="tel:'.e(preg_replace('/\s+/', '', $seo['business_mobile'])).'" dir="ltr">'.$mobile.'</a>';
        }

        $kwMeta = $keywords !== '' ? "\n    <meta name=\"keywords\" content=\"{$keywords}\">" : '';
        $gsc = trim($seo['gsc_verification']) !== ''
            ? "\n    <meta name=\"google-site-verification\" content=\"".e($seo['gsc_verification'])."\">"
            : '';
        $bing = trim($seo['bing_verification']) !== ''
            ? "\n    <meta name=\"msvalidate.01\" content=\"".e($seo['bing_verification'])."\">"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{$title}</title>
    <meta name="description" content="{$desc}">{$kwMeta}
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{$canonical}/">
    <meta property="og:title" content="{$ogTitle}">
    <meta property="og:site_name" content="{$name}">
    <meta property="og:locale" content="{$locale}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{$canonical}/">
    <meta property="og:description" content="{$ogDesc}">
    <meta property="og:image" content="{$ogImage}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$ogTitle}">
    <meta name="twitter:description" content="{$ogDesc}">
    <meta name="twitter:image" content="{$ogImage}">
    <meta name="application-name" content="{$name}">{$gsc}{$bing}
    <meta name="theme-color" content="#2b3340">
    <link rel="icon" href="{$support}/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="application/ld+json">{$jsonLd}</script>
    <style>
        :root { --ink:#1f2933; --muted:#5f6b7a; --line:#9aa5b5; --caption:#2b3340; --accent:#2f6fed; --accent-2:#1e3a8a; --bg:#e8eaee; }
        * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        html,body { margin:0; min-height:100%; }
        body { font-family:Vazirmatn,Tahoma,sans-serif; color:var(--ink); background:var(--bg); min-height:100dvh; display:flex; flex-direction:column; }
        a { color:var(--accent-2); }
        .top { background:linear-gradient(180deg,#3a4454,var(--caption)); color:#f2f4f7; border-bottom:1px solid #1c222c; }
        .top-inner { width:min(1080px,calc(100% - 24px)); margin:0 auto; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px 20px; padding:10px 0 12px; }
        .brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit; }
        .brand-mark { width:44px; height:44px; border-radius:6px; background:#fff; color:#2b3340; display:inline-flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; border:1px solid #c5ccd6; flex:0 0 auto; }
        .brand strong { display:block; font-size:16px; font-weight:800; }
        .brand span { display:block; font-size:11px; opacity:.82; }
        nav ul { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
        nav a { display:inline-flex; align-items:center; min-height:40px; padding:8px 14px; border-radius:3px; text-decoration:none; color:#fff; font-weight:800; font-size:13px; background:linear-gradient(180deg,#4b8dff,var(--accent)); border:1px solid #2458c4; box-shadow:0 4px 12px rgba(0,0,0,.18); }
        .wrap { width:min(1080px,calc(100% - 24px)); margin:0 auto; padding:28px 0 40px; flex:1; }
        .hero { background:linear-gradient(180deg,#fff,#eef1f5); border:1px solid var(--line); border-radius:4px; padding:28px 22px; box-shadow:0 10px 28px rgba(0,0,0,.08); }
        .kicker { margin:0 0 8px; font-size:12px; font-weight:800; color:var(--muted); }
        h1 { margin:0; font-size:clamp(22px,4vw,32px); font-weight:800; }
        .lead { margin:12px 0 0; color:var(--muted); line-height:1.85; font-size:15px; max-width:52rem; }
        .actions { margin-top:20px; display:flex; flex-wrap:wrap; gap:10px; }
        .btn-primary { display:inline-flex; align-items:center; justify-content:center; min-height:46px; padding:10px 18px; border-radius:3px; text-decoration:none; color:#fff; font-weight:800; background:linear-gradient(180deg,#4b8dff,var(--accent)); border:1px solid #2458c4; }
        .note { margin:16px 0 0; font-size:13px; color:var(--muted); }
        footer { border-top:1px solid #c9d0da; background:#fff; }
        .foot-inner { width:min(1080px,calc(100% - 24px)); margin:0 auto; padding:14px 0; display:flex; flex-wrap:wrap; gap:10px 18px; justify-content:space-between; font-size:13px; color:var(--muted); }
        footer a { font-weight:800; text-decoration:none; }
        @media (max-width:720px) { nav a { width:100%; justify-content:center; } }
    </style>
</head>
<body>
    <header class="top">
        <div class="top-inner">
            <a class="brand" href="/">
                <span class="brand-mark" aria-hidden="true">ده</span>
                <span>
                    <strong>{$name}</strong>
                    <span>سامانه پذیرش و کارتابل</span>
                </span>
            </a>
            <nav aria-label="منوی اصلی">
                <ul>
                    <li><a href="{$support}">ورود به سامانه قبض</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="wrap">
        <section class="hero">
            <p class="kicker">ورود سریع</p>
            <h1>{$headline}</h1>
            <p class="lead">{$lead}</p>
            <div class="actions">
                <a class="btn-primary" href="{$support}">{$cta}</a>
            </div>
            <p class="note">آدرس سامانه: <span dir="ltr">{$support}</span></p>
        </section>
    </main>
    <footer>
        <div class="foot-inner">
            <div>{$name}</div>
            <div>{$contact}</div>
        </div>
    </footer>
</body>
</html>
HTML;
    }

    /**
     * Try writing generated apex HTML next to Laravel public (shared-host layout).
     *
     * @return array{ok:bool,message:string,paths:list<string>}
     */
    public static function publishApexLanding(): array
    {
        if (! self::available()) {
            return ['ok' => false, 'message' => 'SEO فقط روی سایت فروشنده فعال است.', 'paths' => []];
        }

        $seo = self::all();
        if (($seo['apex_enabled'] ?? '1') !== '1') {
            return ['ok' => false, 'message' => 'انتشار صفحه دامنه در تنظیمات SEO غیرفعال است.', 'paths' => []];
        }
        $html = self::renderApexHtml();
        $candidates = [
            dirname(public_path(), 2).DIRECTORY_SEPARATOR.'index.html', // .../public_html/index.html when app in public_html/support
            dirname(base_path()).DIRECTORY_SEPARATOR.'index.html',
            public_path('apex-index.html'),
        ];
        $written = [];
        foreach ($candidates as $dest) {
            $dir = dirname($dest);
            if (! is_dir($dir) || ! is_writable($dir)) {
                continue;
            }
            if (is_file($dest)) {
                @copy($dest, $dest.'.bak-seo-'.date('YmdHis'));
            }
            if (@file_put_contents($dest, $html) !== false) {
                $written[] = $dest;
            }
        }
        if (! $written) {
            return [
                'ok' => false,
                'message' => 'مسیر قابل‌نوشتن برای index.html دامنه پیدا نشد. فایل را دانلود و دستی در public_html آپلود کنید.',
                'paths' => [],
            ];
        }

        return [
            'ok' => true,
            'message' => 'صفحه دامنه منتشر شد ('.count($written).' مسیر).',
            'paths' => $written,
        ];
    }
}
