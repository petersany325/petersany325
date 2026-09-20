<?php

namespace Plugins\AdminCore\src\Support;

/**
 * Shop-order commission base — delegates to the unified accounting ledger.
 */
class AccountingLedger
{
    public static function commissionBaseAmount(int $orderId): int
    {
        if (class_exists(\Plugins\Accounting\src\Support\AccCommerce::class)) {
            return \Plugins\Accounting\src\Support\AccCommerce::commissionBaseAmount($orderId);
        }

        return 0;
    }
}
