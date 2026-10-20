<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop',
            'name' => 'فروشگاه',
            'description' => 'هسته فروشگاه؛ هر گزینه فروش افزونه جدا با کلید روشن و خاموش است',
        ];
    }

    public function install(Database $db): void
    {
    }

    public function boot(App $app): void
    {
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/shop', 'label' => 'فروشگاه', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('admin.settings', function (array $items) use ($app): array {
            $on = 0;
            foreach (shop_feature_ids() as $id) {
                if ($app->pluginEnabled($id)) {
                    $on++;
                }
            }
            $items[] = [
                'href' => '/admin/shop',
                'title' => 'فروشگاه',
                'text' => 'قیمت، موجودی، سبد، سفارش، پرداخت، ارسال و مالیات. گزینه روشن: ' . fa_digits((string) $on),
            ];
            return $items;
        });
        $app->listen('site.toolbar', function (array $items): array {
            $path = request_path();
            if (in_array($path, ['/machines', '/cart', '/checkout'], true)) {
                $items[] = ['href' => '/admin/shop', 'label' => 'تنظیمات فروشگاه'];
            }
            return $items;
        });
        $app->router->get('/admin/shop', function () use ($app): void {
            require_role($app, ['admin']);
            $enabled = [];
            foreach ($app->db->all('SELECT id, enabled FROM ak_plugins') as $row) {
                $enabled[(string) $row['id']] = (int) $row['enabled'] === 1;
            }
            shop_extend_schema($app->db);
            $prices = [];
            $stocks = [];
            try {
                foreach ($app->db->all('SELECT * FROM ak_shop_prices') as $row) {
                    $prices[(int) $row['machine_id']] = $row;
                }
                foreach ($app->db->all('SELECT * FROM ak_shop_stock') as $row) {
                    $stocks[(int) $row['machine_id']] = $row;
                }
            } catch (Throwable) {
                $prices = [];
                $stocks = [];
            }
            $app->view->plugin('shop', 'settings', [
                'title' => 'تنظیمات فروشگاه',
                'groups' => shop_feature_groups(),
                'enabled' => $enabled,
                'machines' => shop_machines($app),
                'prices' => $prices,
                'stocks' => $stocks,
                'values' => [
                    'call_for_zero' => shop_flag($app, 'shop-prices', 'call_for_zero', '1'),
                    'show_compare' => shop_flag($app, 'shop-prices', 'show_compare', '1'),
                    'show_qty' => shop_flag($app, 'shop-stock', 'show_qty', '1'),
                    'allow_oversell' => shop_flag($app, 'shop-stock', 'allow_oversell', '0'),
                    'max_qty' => (string) shop_int($app, 'shop-cart', 'max_qty', 20, 1, 99),
                    'min_total' => (string) shop_money_setting($app, 'shop-checkout', 'min_total', 0),
                    'shipping_amount' => (string) shop_money_setting($app, 'shop-shipping', 'amount', 0),
                    'free_over' => (string) shop_money_setting($app, 'shop-shipping', 'free_over', 0),
                    'tax_percent' => (string) shop_int($app, 'shop-tax', 'percent', 10, 0, 100),
                    'on_shipping' => shop_flag($app, 'shop-tax', 'on_shipping', '0'),
                    'transfer_note' => setting($app, 'shop-pay-transfer', 'transfer_note', ''),
                    'zarinpal_on' => shop_flag($app, 'shop-pay-online', 'zarinpal_on', '0'),
                    'zarinpal_sandbox' => shop_flag($app, 'shop-pay-online', 'zarinpal_sandbox', '1'),
                    'zarinpal_merchant' => setting($app, 'shop-pay-online', 'zarinpal_merchant', ''),
                    'zibal_on' => shop_flag($app, 'shop-pay-online', 'zibal_on', '0'),
                    'zibal_sandbox' => shop_flag($app, 'shop-pay-online', 'zibal_sandbox', '0'),
                    'zibal_merchant' => setting($app, 'shop-pay-online', 'zibal_merchant', ''),
                    'saman_on' => shop_flag($app, 'shop-pay-online', 'saman_on', '0'),
                    'saman_terminal' => setting($app, 'shop-pay-online', 'saman_terminal', ''),
                ],
            ]);
        });
        $app->router->post('/admin/shop', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            try {
                shop_save($app);
                flash('success', 'تنظیمات فروشگاه ذخیره شد. روشن و خاموش هر گزینه از همین لحظه اعمال می‌شود.');
            } catch (ShopException $e) {
                flash('error', $e->getMessage());
            } catch (Throwable $e) {
                log_line($app->root, 'shop save ' . $e->getMessage());
                flash('error', 'ذخیره فروشگاه انجام نشد.');
            }
            redirect('/admin/shop');
        });
    }
};
