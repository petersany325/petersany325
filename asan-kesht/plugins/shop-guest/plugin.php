<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-guest',
            'name' => 'فروشگاه: خرید مهمان',
            'description' => 'ثبت سفارش بدون ورود. با خاموش کردن، فقط مشتری واردشده سفارش می‌دهد',
        ];
    }

    public function install(Database $db): void
    {
    }

    public function boot(App $app): void
    {
    }
};
