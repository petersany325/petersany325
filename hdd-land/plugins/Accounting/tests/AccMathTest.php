<?php

require_once dirname(__DIR__).'/src/Support/AccMath.php';

use Plugins\Accounting\src\Support\AccMath;

$fails = 0;
$ok = function (bool $cond, string $msg) use (&$fails) {
    if (! $cond) {
        echo "FAIL: {$msg}\n";
        $fails++;
        return;
    }
    echo "OK: {$msg}\n";
};

$p = AccMath::installmentPlan(20_000_000, 3_000_000, 6);
$ok($p['remain'] === 17_000_000, 'remain = price - down');
$ok($p['total'] === 20_000_000, 'total stays the product price (no ceil overcharge)');
$ok(array_sum($p['amounts']) === $p['remain'], 'installments sum to remain');
$ok(count($p['amounts']) === 6, 'six rows');
$ok($p['amounts'][5] === $p['last'], 'last row is the remainder');

$p2 = AccMath::installmentPlan(100, 0, 3);
$ok($p2['amounts'] === [33, 33, 34], '100/3 = 33+33+34');

$p3 = AccMath::installmentPlan(50, 50, 4);
$ok($p3['remain'] === 0 && array_sum($p3['amounts']) === 0, 'full down payment');

$ok(AccMath::orderLooksPaid('paid'), 'paid is sold');
$ok(AccMath::orderLooksPaid('delivered'), 'delivered is sold');
$ok(! AccMath::orderLooksPaid('pending'), 'pending is not sold');
$ok(AccMath::orderLooksCancelled('cancelled'), 'cancelled');
$ok(AccMath::catalogStockSign('sale') === -1, 'sale decreases shop stock');
$ok(AccMath::catalogStockSign('purchase') === 1, 'purchase increases shop stock');
$ok(AccMath::catalogStockSign('transfer') === 0, 'transfer does not change catalog stock');

if ($fails > 0) {
    fwrite(STDERR, "{$fails} assertion(s) failed\n");
    exit(1);
}
echo "all passed\n";
exit(0);
