<?php

require_once dirname(__DIR__).'/src/Support/AccChart.php';
require_once dirname(__DIR__).'/src/Support/AccJournal.php';

use Plugins\Accounting\src\Support\AccChart;
use Plugins\Accounting\src\Support\AccJournal;

$fails = 0;
$ok = function (bool $cond, string $msg) use (&$fails) {
    if (! $cond) {
        echo "FAIL: {$msg}\n";
        $fails++;

        return;
    }
    echo "OK: {$msg}\n";
};

$tree = AccChart::tree();
$codes = array_column($tree, 'code');
$ok(count($tree) >= 70, 'chart has enough accounts');
$ok(count($codes) === count(array_unique($codes)), 'no duplicate codes');
$ok(in_array('6102', $codes, true), 'recovery income exists');
$ok(in_array('6103', $codes, true), 'repair income exists');
$ok(in_array('6104', $codes, true), 'training income exists');
$ok(in_array('1302', $codes, true), 'installment AR exists');
$ok(in_array('1104', $codes, true), 'gateway cash exists');
$ok(in_array('3208', $codes, true), 'customer wallet liability');
$ok(in_array('3501', $codes, true), 'warranty reserve');
$ok(in_array('7101', $codes, true), 'COGS under group 7');

$groups = array_values(array_filter($tree, fn ($r) => $r['level'] === 'group'));
$ok(array_column($groups, 'code') === ['1', '2', '3', '4', '5', '6', '7', '8', '9'], 'standard 1-9 groups');
$g7 = $groups[6];
$ok($g7['type'] === 'cogs', 'group 7 is COGS not expense');
$g8 = $groups[7];
$ok($g8['type'] === 'expense', 'group 8 is expenses');

foreach ($tree as $row) {
    if ($row['parent']) {
        $ok(str_starts_with($row['code'], $row['parent']), $row['code'].' starts with parent '.$row['parent']);
    }
}

$sale = AccJournal::linesForDocument((object) [
    'type' => 'sale',
    'subtotal' => 1000,
    'discount' => 100,
    'tax' => 90,
    'total' => 990,
    'cogs' => 400,
    'commission_amount' => 50,
    'payment_method' => 'cash',
    'notes' => '',
]);
$ok(AccJournal::isBalanced($sale), 'cash sale journal balances');
$ok(collectDebit($sale, AccChart::defaultMap()['cash']) === 990, 'cash debit = total');
$ok(collectCredit($sale, AccChart::defaultMap()['sales']) === 1000, 'sales credit = subtotal');

$inst = AccJournal::linesForDocument((object) [
    'type' => 'sale',
    'subtotal' => 500,
    'discount' => 0,
    'tax' => 0,
    'total' => 500,
    'cogs' => 200,
    'commission_amount' => 0,
    'payment_method' => 'installment',
    'notes' => '',
]);
$ok(collectDebit($inst, AccChart::defaultMap()['ar_install']) === 500, 'installment hits AR 1302');

$rec = AccJournal::linesForDocument((object) [
    'type' => 'sale',
    'subtotal' => 300,
    'discount' => 0,
    'tax' => 0,
    'total' => 300,
    'cogs' => 0,
    'commission_amount' => 0,
    'payment_method' => 'gateway',
    'notes' => 'ریکاوری هارد',
]);
$ok(collectCredit($rec, AccChart::defaultMap()['recovery']) === 300, 'recovery note maps to 6102');

$purchase = AccJournal::linesForDocument((object) [
    'type' => 'purchase',
    'subtotal' => 800,
    'discount' => 0,
    'tax' => 80,
    'total' => 880,
    'payment_method' => 'unpaid',
]);
$ok(AccJournal::isBalanced($purchase), 'purchase balances');

function collectDebit(array $lines, string $code): int
{
    $n = 0;
    foreach ($lines as $l) {
        if ($l['account'] === $code) {
            $n += $l['debit'];
        }
    }

    return $n;
}
function collectCredit(array $lines, string $code): int
{
    $n = 0;
    foreach ($lines as $l) {
        if ($l['account'] === $code) {
            $n += $l['credit'];
        }
    }

    return $n;
}

if ($fails > 0) {
    fwrite(STDERR, "{$fails} assertion(s) failed\n");
    exit(1);
}
echo "all passed\n";
exit(0);
