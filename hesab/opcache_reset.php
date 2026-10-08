<?php
declare(strict_types=1);
if (($_GET['k'] ?? '') !== 'hsbDeploy2026x') {
    http_response_code(403);
    exit('forbidden');
}
$ok = function_exists('opcache_reset') ? opcache_reset() : false;
header('Content-Type: text/plain; charset=utf-8');
echo $ok ? "opcache_reset=1\n" : "opcache_reset=0\n";
echo 'layout=' . (is_file(__DIR__ . '/views/layout.php') ? filesize(__DIR__ . '/views/layout.php') : 0) . "\n";
echo 'has_open_default=' . (str_contains((string) file_get_contents(__DIR__ . '/views/layout.php'), 'nav-group" open') ? '1' : '0') . "\n";
echo 'time=' . date('c') . "\n";
@unlink(__FILE__);
