<?php
declare(strict_types=1);
/**
 * One-time patch uploader for subdirectory routing fix.
 * Open: https://hdd-land.ir/hesab/patch.php?key=HESAB_PATCH_2026
 * Delete this file after success.
 */
header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';
if ($key !== 'HESAB_PATCH_2026') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$base = 'https://raw.githubusercontent.com/petersany325/petersany325/cursor/hesab-accounting-app-aa3e/hesab';
$files = [
    'app/helpers.php',
    'public/index.php',
    '.htaccess',
    'views/layout.php',
    'views/login.php',
    'views/install.php',
    'views/dashboard.php',
    'views/vouchers.php',
    'views/voucher_form.php',
    'views/voucher_view.php',
    'views/ledger.php',
    'views/parties.php',
    'views/invoices.php',
    'config.sample.php',
];

$root = __DIR__;
$ok = 0;
$fail = 0;

foreach ($files as $rel) {
    $url = $base . '/' . $rel;
    $dest = $root . '/' . $rel;
    $ctx = stream_context_create([
        'http' => ['timeout' => 45, 'header' => "User-Agent: hesab-patch\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || $data === '') {
        echo "FAIL download {$rel}\n";
        $fail++;
        continue;
    }
    $dir = dirname($dest);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        echo "FAIL mkdir {$rel}\n";
        $fail++;
        continue;
    }
    if (file_put_contents($dest, $data) === false) {
        echo "FAIL write {$rel}\n";
        $fail++;
        continue;
    }
    echo "OK {$rel} (" . strlen($data) . " bytes)\n";
    $ok++;
}

echo "\nDone. ok={$ok} fail={$fail}\n";
echo "Next: open /hesab/install and DELETE patch.php\n";
