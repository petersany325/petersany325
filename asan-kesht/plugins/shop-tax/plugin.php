<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-tax',
            'name' => 'فروشگاه: مالیات',
            'description' => 'درصد مالیات روی جمع سفارش فروشگاه',
        ];
    }

    public function install(Database $db): void
    {
        shop_seed($db, 'shop-tax', [
            'percent' => '10',
            'on_shipping' => '0',
        ]);
    }

    public function boot(App $app): void
    {
    }
};