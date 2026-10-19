<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-pay-check',
            'name' => 'فروشگاه: پرداخت با چک',
            'description' => 'روش پرداخت چک برای سفارش فروشگاه',
        ];
    }

    public function install(Database $db): void
    {
    }

    public function boot(App $app): void
    {
    }
};
