<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-checkout',
            'name' => 'فروشگاه: ثبت سفارش',
            'description' => 'تسویه سبد و پیگیری سفارش مشتری. پرداخت آنلاین انجام نمی‌شود',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS ak_shop_orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                number VARCHAR(24) NOT NULL,
                user_id INT UNSIGNED NULL,
                name VARCHAR(120) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                province VARCHAR(80) NOT NULL,
                city VARCHAR(80) NOT NULL,
                address VARCHAR(300) NOT NULL,
                note VARCHAR(500) NOT NULL,
                status VARCHAR(20) NOT NULL,
                payment_method VARCHAR(20) NOT NULL,
                subtotal BIGINT NOT NULL,
                shipping BIGINT NOT NULL,
                tax BIGINT NOT NULL,
                total BIGINT NOT NULL,
                stock_applied TINYINT(1) NOT NULL DEFAULT 0,
                ip VARCHAR(45) NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL,
                UNIQUE KEY number (number),
                KEY user_created (user_id, id),
                KEY status_created (status, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_shop_order_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                machine_id INT UNSIGNED NOT NULL,
                code VARCHAR(40) NOT NULL,
                name VARCHAR(180) NOT NULL,
                qty INT NOT NULL,
                unit_price BIGINT NOT NULL,
                line_total BIGINT NOT NULL,
                KEY order_id (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        shop_seed($db, 'shop-checkout', ['min_total' => '0']);
    }

    public function boot(App $app): void
    {
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/shop/orders', 'label' => 'سفارش فروشگاه', 'roles' => ['admin', 'employee']];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_shop_orders WHERE status = "submitted"');
            $cards[] = [
                'label' => 'سفارش تازه فروشگاه',
                'value' => fa_digits((string) ($row['c'] ?? 0)),
                'href' => '/admin/shop/orders',
                'roles' => ['admin', 'employee'],
            ];
            return $cards;
        });
        $app->listen('account.cards', function (array $cards) use ($app): array {
            $user = $app->auth->user();
            if (!$user || !in_array((string) $user['role'], ['customer', 'admin'], true)) {
                return $cards;
            }
            $row = $app->db->one(
                'SELECT COUNT(*) AS c FROM ak_shop_orders WHERE user_id = ? OR (phone <> "" AND phone = ?)',
                [(int) $user['id'], (string) ($user['phone'] ?? '')]
            );
            $cards[] = [
                'href' => '/account/orders',
                'label' => 'سفارش‌های فروشگاه',
                'value' => fa_digits((string) ($row['c'] ?? 0)),
            ];
            return $cards;
        });
        $app->router->get('/checkout', function () use ($app): void {
            $this->form($app);
        });
        $app->router->post('/checkout', function () use ($app): void {
            csrf_check();
            $access = shop_checkout_access($app);
            if (!$access['ok']) {
                flash('error', $access['message']);
                redirect('/checkout');
            }
            try {
                shop_place_order($app, $access['user']);
                redirect('/checkout/thanks');
            } catch (ShopException $e) {
                flash('error', $e->getMessage());
                redirect('/checkout');
            } catch (Throwable $e) {
                log_line($app->root, 'shop order ' . $e->getMessage());
                flash('error', 'سفارش ثبت نشد.');
                redirect('/checkout');
            }
        });
        $app->router->get('/checkout/thanks', function () use ($app): void {
            $thanks = $_SESSION['ak_shop_thanks'] ?? null;
            unset($_SESSION['ak_shop_thanks']);
            if (!is_array($thanks)) {
                redirect('/cart');
            }
            $method = (string) ($thanks['payment'] ?? '');
            $note = '';
            if ($method === 'transfer') {
                $note = setting($app, 'shop-pay-transfer', 'transfer_note', '');
            }
            $app->view->plugin('shop-checkout', 'thanks', [
                'title' => 'سفارش ثبت شد',
                'thanks' => $thanks,
                'method' => shop_payment_methods($app)[$method] ?? $method,
                'note' => $note,
            ], 'layouts/site');
        });
        $app->router->get('/account/orders', function () use ($app): void {
            $user = require_user($app);
            $orders = shop_orders(
                $app,
                'user_id = ? OR (phone <> "" AND phone = ?)',
                [(int) $user['id'], (string) ($user['phone'] ?? '')],
                40
            );
            $app->view->plugin('shop-checkout', 'mine', [
                'title' => 'سفارش‌های من',
                'orders' => $orders,
            ], 'layouts/site');
        });
        $app->router->get('/admin/shop/orders', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $app->view->plugin('shop-checkout', 'orders', [
                'title' => 'سفارش‌های فروشگاه',
                'orders' => shop_orders($app, '1 = 1', [], 80),
                'statuses' => shop_order_statuses(),
                'methods' => shop_payment_methods($app) + ['cash' => 'نقد هنگام تحویل', 'transfer' => 'کارت به کارت', 'check' => 'چک'],
            ]);
        });
        $app->router->post('/admin/shop/orders/status', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            try {
                shop_set_order_status($app, (int) post_string('id', 12), post_string('status', 20));
                flash('success', 'وضعیت سفارش ذخیره شد.');
            } catch (ShopException $e) {
                flash('error', $e->getMessage());
            } catch (Throwable $e) {
                log_line($app->root, 'shop status ' . $e->getMessage());
                flash('error', 'وضعیت ذخیره نشد.');
            }
            redirect('/admin/shop/orders');
        });
    }

    private function form(App $app): void
    {
        if (!shop_ready($app, 'shop-checkout')) {
            flash('error', 'ثبت سفارش خاموش است.');
            redirect('/cart');
        }
        $access = shop_checkout_access($app);
        $lines = shop_cart_lines($app);
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += $line['line'];
        }
        $user = $access['user'];
        $app->view->plugin('shop-checkout', 'checkout', [
            'title' => 'ثبت سفارش',
            'lines' => $lines,
            'totals' => shop_totals($app, $subtotal),
            'methods' => shop_payment_methods($app),
            'access' => $access,
            'name' => $user ? (string) $user['name'] : '',
            'phone' => $user ? (string) ($user['phone'] ?? '') : '',
        ], 'layouts/site');
    }
};
