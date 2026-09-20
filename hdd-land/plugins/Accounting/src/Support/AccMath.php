<?php

namespace Plugins\Accounting\src\Support;

/**
 * Pure money/qty helpers — no Laravel so they can be unit-tested.
 */
class AccMath
{
    /**
     * Split remaining amount into monthly installments without overcharging.
     * First (n-1) rows use the floor share; the last row takes the remainder.
     *
     * @return array{remain:int,monthly:int,last:int,total:int,amounts:list<int>}
     */
    public static function installmentPlan(int $price, int $down, int $months): array
    {
        $price = max(0, $price);
        $down = max(0, min($price, $down));
        $months = max(1, $months);
        $remain = $price - $down;
        if ($months === 1) {
            return [
                'remain' => $remain,
                'monthly' => $remain,
                'last' => $remain,
                'total' => $price,
                'amounts' => [$remain],
            ];
        }
        $monthly = intdiv($remain, $months);
        $amounts = [];
        $used = 0;
        for ($i = 1; $i < $months; $i++) {
            $amounts[] = $monthly;
            $used += $monthly;
        }
        $last = $remain - $used;
        $amounts[] = $last;

        return [
            'remain' => $remain,
            'monthly' => $monthly,
            'last' => $last,
            'total' => $price,
            'amounts' => $amounts,
        ];
    }

    public static function orderLooksPaid(string $status): bool
    {
        $status = strtolower(trim($status));

        return in_array($status, [
            'paid', 'processing', 'completed', 'complete', 'shipped',
            'delivered', 'fulfilled', 'success', 'verified',
        ], true);
    }

    public static function orderLooksCancelled(string $status): bool
    {
        $status = strtolower(trim($status));

        return in_array($status, ['cancelled', 'canceled', 'failed', 'refunded', 'void'], true);
    }

    /** +1 inbound (purchase/stock_in), -1 outbound (sale/stock_out), 0 no catalog move. */
    public static function catalogStockSign(string $type): int
    {
        return match ($type) {
            'purchase', 'stock_in' => 1,
            'sale', 'stock_out' => -1,
            default => 0,
        };
    }
}
