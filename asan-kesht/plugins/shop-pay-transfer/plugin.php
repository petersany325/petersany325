<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-pay-transfer',
            'name' => 'فروشگاه: کارت به کارت',
            'description' => 'روش پرداخت واریز. شماره حساب در توضیح همین افزونه نوشته می‌شود',
        ];
    }

    public function install(Database $db): void
    {
        shop_seed($db, 'shop-pay-transfer', [
            'transfer_note' => 'مبلغ را به حسابی که شرکت اعلام می‌کند واریز کنید و شماره پیگیری را با تیکت بفرستید.',
        ]);
    }

    public function boot(App $app): void
    {
    }
};
