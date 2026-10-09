<?php
declare(strict_types=1);
/**
 * Pull selected files from the feature branch into this hesab root.
 * Open: https://hdd-land.ir/hesab/patch.php?key=HESAB_PATCH_2026
 * Delete this file after success (or leave for future deploys).
 */
header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';
if ($key !== 'HESAB_PATCH_2026') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

// Pin to commit SHA so CDNs cannot serve a stale branch tip.
$sha = '2464354ce0437f92567581a8d93216ce8634eaf9';
$bust = rawurlencode((string) time());
$bases = [
    'https://raw.githubusercontent.com/petersany325/petersany325/' . $sha . '/hesab/',
    'https://cdn.jsdelivr.net/gh/petersany325/petersany325@' . $sha . '/hesab/',
];
$files = [
    'app/helpers.php',
    'app/bootstrap.php',
    'app/SettingsStore.php',
    'app/Sms.php',
    'app/License.php',
    'app/InvoiceSettings.php',
    'app/Auth.php',
    'app/Migrator.php',
    'app/Permission.php',
    'app/Shortcuts.php',
    'app/routes_app.php',
    'app/routes_mobile.php',
    'app/routes_settings.php',
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
    'views/users.php',
    'views/settings_accounting.php',
    'views/settings_shortcuts.php',
    'views/settings_hub.php',
    'views/settings_invoice.php',
    'views/settings_sms.php',
    'views/settings_license.php',
    'views/settings_profile.php',
    'views/print_invoice.php',
    'views/mobile/login.php',
    'assets/js/app.js',
    'assets/css/app.css',
    'assets/js/mobile.js',
    'assets/css/mobile.css',
    'opcache_reset.php',
    'patch.php',
    'config.sample.php',
];

$root = __DIR__;
$ok = 0;
$fail = 0;

foreach ($files as $rel) {
    $dest = $root . '/' . $rel;
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 45,
            'header' => "User-Agent: hesab-patch\r\nCache-Control: no-cache\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = false;
    foreach ($bases as $base) {
        $url = $base . $rel . (str_contains($base, 'raw.githubusercontent') ? ('?t=' . $bust) : '');
        $candidate = @file_get_contents($url, false, $ctx);
        if ($candidate === false || $candidate === '') {
            continue;
        }
        // Reject known-stale layout bootstrap if a cleaner copy is available later.
        if ($rel === 'views/layout.php' && str_contains($candidate, 'hesab_pull')) {
            continue;
        }
        $data = $candidate;
        break;
    }
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
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache_reset=1\n";
}
echo "time=" . date('c') . "\n";
