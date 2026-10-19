<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-stock',
            'name' => 'فروشگاه: موجودی',
            'description' => 'نمایش موجودی و جلوگیری از فروش بیش از تعداد. خاموش شدن عدد موجودی را پاک نمی‌کند',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_shop_stock (
                machine_id INT UNSIGNED PRIMARY KEY,
                qty INT NOT NULL DEFAULT 0,
                track TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        shop_seed($db, 'shop-stock', [
            'show_qty' => '1',
            'allow_oversell' => '0',
        ]);
    }

    public function boot(App $app): void
    {
        $app->listen('shop.machine.bits', function (array $payload) use ($app): array {
            $machine = $payload['machine'] ?? [];
            if (!is_array($machine)) {
                return $payload;
            }
            $payload['stock_html'] = shop_stock_html($app, (int) ($machine['id'] ?? 0));
            return $payload;
        });
    }
};
