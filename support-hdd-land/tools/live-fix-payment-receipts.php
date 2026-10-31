<?php
/**
 * One-shot live hotfix for payment-receipts HTTP 500.
 *
 * Cause on live: routes/web.php resolves PaymentReceiptController without
 * namespace (missing use import), so Laravel throws:
 *   Class "PaymentReceiptController" does not exist
 *
 * Usage:
 * 1) Upload this file to: public_html/tmr/_pr_fix.php
 * 2) Open: https://hdd-land.ir/tmr/_pr_fix.php
 * 3) Confirm output contains resolve=1 and action=...PaymentReceiptController@index
 * 4) Delete _pr_fix.php
 */
header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$web = $root.'/routes/web.php';
if (! is_file($web)) {
    echo "web.php missing at {$web}\n";
    exit(1);
}

$c = file_get_contents($web);
$changed = false;

if (! str_contains($c, 'use App\\Http\\Controllers\\PaymentReceiptController;')) {
    if (preg_match_all('/^use App\\\\Http\\\\Controllers\\\\.+;$/m', $c, $all, PREG_OFFSET_CAPTURE) && $all[0]) {
        $last = end($all[0]);
        $pos = $last[1] + strlen($last[0]);
        $c = substr($c, 0, $pos)."\nuse App\\Http\\Controllers\\PaymentReceiptController;".substr($c, $pos);
    } else {
        $c = preg_replace('/^<\?php\s*/', "<?php\n\nuse App\\Http\\Controllers\\PaymentReceiptController;\n", $c, 1);
    }
    $changed = true;
    echo "added use import\n";
}

$fqcn = '\\App\\Http\\Controllers\\PaymentReceiptController::class';
$repls = [
    '[PaymentReceiptController::class, \'index\']' => '['.$fqcn.', \'index\']',
    '[PaymentReceiptController::class, \'show\']' => '['.$fqcn.', \'show\']',
    '[PaymentReceiptController::class, \'image\']' => '['.$fqcn.', \'image\']',
    '[PaymentReceiptController::class, \'approve\']' => '['.$fqcn.', \'approve\']',
    '[PaymentReceiptController::class, \'reject\']' => '['.$fqcn.', \'reject\']',
    "'PaymentReceiptController@index'" => '['.$fqcn.', \'index\']',
    "'PaymentReceiptController@show'" => '['.$fqcn.', \'show\']',
    "'PaymentReceiptController@image'" => '['.$fqcn.', \'image\']',
    "'PaymentReceiptController@approve'" => '['.$fqcn.', \'approve\']',
    "'PaymentReceiptController@reject'" => '['.$fqcn.', \'reject\']',
];
foreach ($repls as $from => $to) {
    if (str_contains($c, $from)) {
        $c = str_replace($from, $to, $c);
        $changed = true;
        echo "normalized controller reference\n";
    }
}

if ($changed) {
    file_put_contents($web, $c);
    echo "web.php saved\n";
} else {
    echo "web.php already ok\n";
}

foreach (explode("\n", $c) as $i => $line) {
    if (str_contains($line, 'PaymentReceipt') || str_contains($line, 'payment-receipt')) {
        echo ($i + 1).':'.$line."\n";
    }
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Illuminate\Support\Facades\Artisan::call('optimize:clear');
    echo "optimize_clear_ok\n".Artisan::output();
} catch (Throwable $e) {
    echo 'optimize_fail '.$e->getMessage()."\n";
}

echo 'fqcn='.(int) class_exists('App\\Http\\Controllers\\PaymentReceiptController')."\n";
echo 'table='.(int) Illuminate\Support\Facades\Schema::hasTable('payment_receipts')."\n";

if (! Illuminate\Support\Facades\Schema::hasTable('payment_receipts')) {
    try {
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        echo 'migrate='.Artisan::output()."\n";
    } catch (Throwable $e) {
        echo 'migrate_fail '.$e->getMessage()."\n";
    }
}

$route = app('router')->getRoutes()->getByName('payment-receipts.index');
echo 'action='.($route ? $route->getActionName() : 'none')."\n";

try {
    app()->make('App\\Http\\Controllers\\PaymentReceiptController');
    echo "resolve=1\n";
} catch (Throwable $e) {
    echo 'resolve=0 '.$e->getMessage()."\n";
}

echo "DONE — delete this file now.\n";
