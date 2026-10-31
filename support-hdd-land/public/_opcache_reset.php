<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['t'] ?? '') !== 'opcache-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

echo 'PHP='.PHP_VERSION."\n";
if (function_exists('opcache_reset')) {
    echo 'opcache_reset='.(opcache_reset() ? 'yes' : 'no')."\n";
} else {
    echo "opcache_reset=unavailable\n";
}

$root = dirname(__DIR__);
foreach ([
    'app/Services/ReleaseChangeBoardService.php',
    'app/Http/Controllers/AppReleaseAdminController.php',
    'routes/web.php',
    'resources/views/licenses/releases.blade.php',
] as $rel) {
    $p = $root.'/'.$rel;
    echo $rel.' size='.(is_file($p) ? filesize($p) : 'missing')."\n";
}

echo "DONE\n";
@unlink(__FILE__);
