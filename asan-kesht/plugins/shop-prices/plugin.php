<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-prices',
            'name' => 'فروشگاه: قیمت',
            'description' => 'نمایش قیمت ریالی مدل‌ها. خاموش شدن داده قیمت را پاک نمی‌کند',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_shop_prices (
                machine_id INT UNSIGNED PRIMARY KEY,
                price BIGINT NOT NULL DEFAULT 0,
                compare_at BIGINT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        shop_seed($db, 'shop-prices', [
            'call_for_zero' => '1',
            'show_compare' => '1',
        ]);
    }

    public function boot(App $app): void
    {
        $app->listen('shop.machine.bits', function (array $payload) use ($app): array {
            $machine = $payload['machine'] ?? [];
            if (!is_array($machine)) {
                return $payload;
            }
            $payload['price_html'] = shop_price_html($app, (int) ($machine['id'] ?? 0));
            return $payload;
        });
    }
};
