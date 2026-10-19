<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-cart',
            'name' => 'فروشگاه: سبد خرید',
            'description' => 'افزودن مدل به سبد و ویرایش تعداد',
        ];
    }

    public function install(Database $db): void
    {
        shop_seed($db, 'shop-cart', ['max_qty' => '20']);
    }

    public function boot(App $app): void
    {
        $app->listen('site.aside', function (array $items) use ($app): array {
            if (!shop_ready($app, 'shop-cart')) {
                return $items;
            }
            $count = shop_cart_count();
            $label = 'سبد خرید';
            if ($count > 0) {
                $label .= ' (' . fa_digits((string) $count) . ')';
            }
            $items[] = ['href' => '/cart', 'label' => $label];
            return $items;
        });
        $app->listen('shop.machine.bits', function (array $payload) use ($app): array {
            if (!shop_ready($app, 'shop-cart')) {
                return $payload;
            }
            $machine = $payload['machine'] ?? [];
            if (!is_array($machine) || shop_line_ok($app, $machine, 1) !== null) {
                return $payload;
            }
            $max = shop_int($app, 'shop-cart', 'max_qty', 20, 1, 99);
            $id = (int) $machine['id'];
            $payload['actions_html'] = '<form class="cart-add" method="post" action="' . e(url('/cart/add')) . '">'
                . csrf_field()
                . '<input type="hidden" name="machine_id" value="' . e((string) $id) . '">'
                . '<label>تعداد <input name="qty" type="number" min="1" max="' . e((string) $max) . '" value="1"></label>'
                . '<button class="btn" type="submit">افزودن به سبد</button></form>';
            return $payload;
        });
        $app->router->get('/cart', function () use ($app): void {
            if (!shop_ready($app, 'shop-cart')) {
                redirect('/machines');
            }
            $lines = shop_cart_lines($app);
            $subtotal = 0;
            foreach ($lines as $line) {
                $subtotal += $line['line'];
            }
            $app->view->plugin('shop-cart', 'cart', [
                'title' => 'سبد خرید',
                'lines' => $lines,
                'totals' => shop_totals($app, $subtotal),
                'checkout' => shop_ready($app, 'shop-checkout'),
                'max' => shop_int($app, 'shop-cart', 'max_qty', 20, 1, 99),
            ], 'layouts/site');
        });
        $app->router->post('/cart/add', function () use ($app): void {
            csrf_check();
            try {
                shop_cart_add($app, (int) post_string('machine_id', 12), (int) post_string('qty', 4));
                flash('success', 'به سبد اضافه شد.');
                redirect('/cart');
            } catch (ShopException $e) {
                flash('error', $e->getMessage());
                redirect('/machines');
            }
        });
        $app->router->post('/cart/update', function () use ($app): void {
            csrf_check();
            $posted = $_POST['qty'] ?? [];
            if (!is_array($posted)) {
                redirect('/cart');
            }
            $cart = [];
            foreach ($posted as $id => $qty) {
                $machineId = (int) $id;
                $count = (int) preg_replace('/\D+/', '', en_digits((string) $qty));
                if ($machineId < 1 || $count < 1) {
                    continue;
                }
                $machine = $app->db->one('SELECT * FROM ak_machines WHERE id = ?', [$machineId]);
                if (!$machine) {
                    continue;
                }
                $error = shop_line_ok($app, $machine, $count);
                if ($error !== null) {
                    flash('error', (string) $machine['name'] . ': ' . $error);
                    redirect('/cart');
                }
                $cart[$machineId] = $count;
            }
            shop_cart_set($cart);
            flash('success', 'سبد ذخیره شد.');
            redirect('/cart');
        });
        $app->router->post('/cart/remove', function () use ($app): void {
            csrf_check();
            $cart = shop_cart_get();
            unset($cart[(int) post_string('machine_id', 12)]);
            shop_cart_set($cart);
            flash('success', 'از سبد برداشته شد.');
            redirect('/cart');
        });
    }
};
