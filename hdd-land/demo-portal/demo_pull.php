<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
$token = (string) ($_GET['t'] ?? '');
if ($token !== 'hl-demo-20260919') {
    http_response_code(403);
    exit("no\n");
}

$sha = preg_replace('/[^a-f0-9]/', '', strtolower((string) ($_GET['sha'] ?? '')));
if (strlen($sha) !== 40) {
    http_response_code(400);
    exit("bad-sha\n");
}

$map = [
    'index.php' => 'hdd-land/demo-portal/index.php',
    'bootstrap.php' => 'hdd-land/demo-portal/bootstrap.php',
    'views.php' => 'hdd-land/demo-portal/views.php',
    '.htaccess' => 'hdd-land/demo-portal/.htaccess',
    'robots.txt' => 'hdd-land/demo-portal/robots.txt',
    'config.sample.php' => 'hdd-land/demo-portal/config.sample.php',
    'assets/demo.css' => 'hdd-land/demo-portal/assets/demo.css',
    'demo_pull.php' => 'hdd-land/demo-portal/demo_pull.php',
];

$root = __DIR__;
foreach ($map as $dest => $rel) {
    $url = 'https://raw.githubusercontent.com/petersany325/petersany325/' . $sha . '/' . $rel;
    $bin = @file_get_contents($url);
    if ($bin === false || $bin === '') {
        echo "FAIL $dest\n";
        continue;
    }
    $path = $root . '/' . $dest;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $bin);
    echo "OK $dest " . strlen($bin) . "\n";
}
echo "done\n";
