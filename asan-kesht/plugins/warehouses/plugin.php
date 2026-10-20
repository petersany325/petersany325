<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'warehouses',
            'name' => 'حسابداری انبار',
            'description' => 'چند انبار با میانگین متحرک. فاکتور با ماشین موجودی را عوض می‌کند. ارسال امانی فروش نیست و فقط کسری تأییدشده هزینه می‌شود',
        ];
    }

    public function install(Database $db): void
    {
        warehouse_schema($db);
    }

    public function boot(App $app): void
    {
        warehouse_schema($app->db);
        if (!$app->pluginEnabled('accounting')) {
            warehouse_routes($app);
        }
        $roles = warehouse_roles();
        $app->listen('admin.menu', function (array $items) use ($roles): array {
            $items[] = ['href' => '/admin/accounting/warehouses', 'label' => 'انبارها', 'roles' => $roles];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = [
                'href' => '/admin/accounting/warehouses',
                'title' => 'انبارها',
                'text' => 'فاکتور با ماشین، ورود یا خروج انبار می‌سازد. ارسال امانی فروش نیست. فقط کسری سرقت، آسیب یا اسقاط هزینه می‌شود. موجودی فروشگاه جداست.',
            ];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app, $roles): array {
            $cards[] = [
                'label' => 'ارزش انبار',
                'value' => money(warehouse_value($app)),
                'href' => '/admin/accounting/warehouses',
                'roles' => $roles,
            ];
            return $cards;
        });
    }
};
