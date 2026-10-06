@php
    $seoMeta = null;
    try {
        if (class_exists(\App\Support\SeoSettings::class)) {
            $seoMeta = \App\Support\SeoSettings::metaPayload(request(), trim($__env->yieldContent('title')) ?: null);
        }
    } catch (\Throwable $e) {
        $seoMeta = null;
    }
@endphp
@if(is_array($seoMeta) && !empty($seoMeta['enabled']))
    <meta name="description" content="{{ $seoMeta['description'] }}">
    @if(($seoMeta['keywords'] ?? '') !== '')
        <meta name="keywords" content="{{ $seoMeta['keywords'] }}">
    @endif
    <meta name="robots" content="{{ $seoMeta['robots'] }}">
    <link rel="canonical" href="{{ $seoMeta['canonical'] }}">
    <meta property="og:locale" content="{{ str_replace('_', '-', $seoMeta['locale']) }}">
    <meta property="og:type" content="{{ $seoMeta['og_type'] }}">
    <meta property="og:site_name" content="{{ $seoMeta['site_name'] }}">
    <meta property="og:title" content="{{ $seoMeta['og_title'] }}">
    <meta property="og:description" content="{{ $seoMeta['og_description'] }}">
    <meta property="og:url" content="{{ $seoMeta['og_url'] }}">
    @if(($seoMeta['og_image'] ?? '') !== '')
        <meta property="og:image" content="{{ $seoMeta['og_image'] }}">
    @endif
    <meta name="twitter:card" content="{{ $seoMeta['twitter_card'] }}">
    <meta name="twitter:title" content="{{ $seoMeta['og_title'] }}">
    <meta name="twitter:description" content="{{ $seoMeta['og_description'] }}">
    @if(($seoMeta['og_image'] ?? '') !== '')
        <meta name="twitter:image" content="{{ $seoMeta['og_image'] }}">
    @endif
    @if(($seoMeta['gsc_verification'] ?? '') !== '')
        <meta name="google-site-verification" content="{{ $seoMeta['gsc_verification'] }}">
    @endif
    @if(($seoMeta['bing_verification'] ?? '') !== '')
        <meta name="msvalidate.01" content="{{ $seoMeta['bing_verification'] }}">
    @endif
    @if(!empty($seoMeta['json_ld']['graph']))
        <script type="application/ld+json">{!! json_encode($seoMeta['json_ld']['graph'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}</script>
    @endif
@endif
