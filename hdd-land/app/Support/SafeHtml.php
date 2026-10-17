<?php

namespace App\Support;

final class SafeHtml
{
    private const ALLOWED_TAGS = '<a><abbr><article><aside><b><blockquote><br><button><caption><code><col><colgroup><dd><del><details><div><dl><dt><em><fieldset><figcaption><figure><footer><form><h1><h2><h3><h4><h5><h6><header><meta charset="utf-8"><hr><i><img><input><label><legend><li><main><mark><nav><ol><option><p><picture><pre><section><select><small><source><span><strong><sub><summary><sup><table><tbody><td><textarea><tfoot><th><thead><tr><u><ul><video>';

    public static function clean(?string $html): string
    {
        $html = strip_tags((string) $html, self::ALLOWED_TAGS);

        // Remove inline event handlers and dangerous URL schemes while preserving layout markup.
        $html = preg_replace('/\s+on[a-z0-9_-]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? '';
        $html = preg_replace('/\s+(href|src|action|formaction)\s*=\s*(["\'])\s*(?:javascript|vbscript|data):.*?\2/iu', ' $1="#"', $html) ?? '';
        $html = preg_replace('/\s+srcdoc\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? '';

        return $html;
    }
}
