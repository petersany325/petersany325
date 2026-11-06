<?php

namespace App\Support;

/**
 * Customer cabinet menu — same plugins as staff, customer-facing.
 */
class StudioCustomerDesk
{
    /** @return list<array{label:string,url:string,icon:string,match:string}> */
    public static function menu(): array
    {
        return [
            ['label' => 'میز کار', 'url' => '/account', 'icon' => '▣', 'match' => 'account'],
            ['label' => 'خرید', 'url' => '/account/buy', 'icon' => '⬇', 'match' => 'account/buy'],
            ['label' => 'فروش / سفارش', 'url' => '/account/sell', 'icon' => '⬆', 'match' => 'account/sell'],
            ['label' => 'سفارش‌ها', 'url' => '/account/orders', 'icon' => '☰', 'match' => 'account/orders'],
            ['label' => 'فاکتورها', 'url' => '/account/invoices', 'icon' => '▤', 'match' => 'account/invoices'],
            ['label' => 'تیکت‌ها', 'url' => '/account/tickets', 'icon' => '✉', 'match' => 'account/tickets'],
            ['label' => 'پشتیبانی', 'url' => '/account/support', 'icon' => '?', 'match' => 'account/support'],
            ['label' => 'کیف پول', 'url' => '/account/wallet', 'icon' => '◈', 'match' => 'account/wallet'],
            ['label' => 'سریال‌ها', 'url' => '/account/serials', 'icon' => '☰', 'match' => 'account/serials'],
            ['label' => 'سبد خرید', 'url' => StudioPlugins::cartUrl(), 'icon' => '🛒', 'match' => 'cart'],
            ['label' => 'ورود', 'url' => '/auth/customer', 'icon' => '→', 'match' => 'auth/customer'],
        ];
    }
}
