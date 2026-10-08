<?php
declare(strict_types=1);
if (($_GET['k'] ?? '') !== 'hsbDeploy2026x') {
    http_response_code(403);
    exit('forbidden');
}
$ok = function_exists('opcache_reset') ? opcache_reset() : false;
header('Content-Type: text/plain; charset=utf-8');
echo $ok ? "opcache_reset=1\n" : "opcache_reset=0\n";
$layout = (string) @file_get_contents(__DIR__ . '/views/layout.php');
echo 'has_win_menubar=' . (str_contains($layout, 'win-menubar') ? '1' : '0') . "\n";
echo 'has_build6=' . (str_contains($layout, 'build 6') ? '1' : '0') . "\n";
echo 'time=' . date('c') . "\n";
@unlink(__FILE__);
