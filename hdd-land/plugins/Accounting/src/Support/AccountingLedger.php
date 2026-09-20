<?php

namespace Plugins\Accounting\src\Support;

/**
 * Compatibility alias used by StaffHR commission on shop orders.
 */
class AccountingLedger
{
    public static function commissionBaseAmount(int $orderId): int
    {
        return AccCommerce::commissionBaseAmount($orderId);
    }
}
