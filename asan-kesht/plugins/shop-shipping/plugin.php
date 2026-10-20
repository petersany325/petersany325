<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-shipping',
            'name' => 'فروشگاه: هزینه ارسال',
            'description' => 'مبلغ ثابت ارسال و آستانه ارسال رایگان روی سفارش',
        ];
    }

    public function install(Database $db): void
    {
        shop_seed($db, 'shop-shipping', [
            'amount' => '0',
            'free_over' => '0',
        ]);
    }

    public function boot(App $app): void
    {
    }
};
