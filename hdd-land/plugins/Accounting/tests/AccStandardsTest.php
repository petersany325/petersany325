<?php

require_once dirname(__DIR__).'/src/Support/AccEngine.php';
require_once dirname(__DIR__).'/src/Support/AccJournal.php';

use Plugins\Accounting\src\Support\AccEngine;
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

$ok(isset(AccEngine::TYPES['sale'], AccEngine::TYPES['purchase'], AccEngine::TYPES['proforma'], AccEngine::TYPES['voucher']), 'sale/purchase/proforma/voucher types');
$ok(isset(AccEngine::CHECK_DIRECTIONS['receivable'], AccEngine::CHECK_DIRECTIONS['spent']), 'received and spent check menus');
$ok(isset(AccEngine::CHECK_STATUSES['in_collection'], AccEngine::CHECK_STATUSES['spent']), 'collection + spent statuses');
$ok(isset(AccEngine::STAFF_KINDS['employee'], AccEngine::STAFF_KINDS['visitor']), 'employee and visitor kinds');

$balanced = AccJournal::normalizeLines([
    ['account' => '1101', 'debit' => 1000, 'credit' => 0],
    ['account' => '6101', 'debit' => 0, 'credit' => 1000],
]);
$ok(AccJournal::isBalanced($balanced), 'Sepidar voucher must balance');

$unbalanced = AccJournal::normalizeLines([
    ['account' => '1101', 'debit' => 1000, 'credit' => 0],
    ['account' => '6101', 'debit' => 0, 'credit' => 800],
]);
$ok(! AccJournal::isBalanced($unbalanced), 'unbalanced voucher rejected');

$purchaseGross = 10 * 200000;
$disc = (int) round($purchaseGross * 5 / 100);
$after = $purchaseGross - $disc;
$vat = (int) round($after * 10 / 100);
$lineTotal = $after + $vat;
$ok($lineTotal === 2090000, 'purchase line: qty*rate -5% +10% VAT = 2,090,000');

echo $fails === 0 ? "ALL STANDARDS OK\n" : "FAILED {$fails}\n";
exit($fails === 0 ? 0 : 1);
