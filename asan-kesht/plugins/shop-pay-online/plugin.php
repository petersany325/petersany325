<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/shop/lib.php';
require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'shop-pay-online',
            'name' => 'فروشگاه: پرداخت آنلاین',
            'description' => 'زرین‌پال، زیبال و درگاه سپ بانک سامان؛ فروش بعد از تأیید بانک در سود و زیان می‌آید',
        ];
    }

    public function install(Database $db): void
    {
        shop_seed($db, 'shop-pay-online', [
            'zarinpal_on' => '0',
            'zarinpal_sandbox' => '1',
            'zarinpal_merchant' => '',
            'zibal_on' => '0',
            'zibal_sandbox' => '0',
            'zibal_merchant' => '',
            'saman_on' => '0',
            'saman_terminal' => '',
        ]);
    }

    public function boot(App $app): void
    {
        $app->router->get('/pay/online/{provider}', function (array $params) use ($app): void {
            shop_online_callback($app, (string) ($params['provider'] ?? ''));
        });
        $app->router->post('/pay/online/{provider}', function (array $params) use ($app): void {
            shop_online_callback($app, (string) ($params['provider'] ?? ''));
        });
        $app->router->get('/account/orders/{id}/pay', function (array $params) use ($app): void {
            $user = require_user($app);
            $id = (int) ($params['id'] ?? 0);
            shop_extend_schema($app->db);
            $order = $app->db->one('SELECT * FROM ak_shop_orders WHERE id = ?', [$id]);
            $phone = (string) ($user['phone'] ?? '');
            $owner = $order
                && ((int) ($order['user_id'] ?? 0) === (int) $user['id']
                    || ($phone !== '' && $phone === (string) $order['phone']));
            if (!$order || !$owner) {
                flash('error', 'این سفارش برای حساب شما نیست.');
                redirect('/account/orders');
            }
            if ((string) $order['payment_status'] === 'paid') {
                flash('success', 'این سفارش قبلاً پرداخت شده است.');
                redirect('/account/orders');
            }
            if ((string) $order['status'] === 'cancelled' || !shop_online_is((string) $order['payment_method'])) {
                flash('error', 'این سفارش پرداخت آنلاین ندارد.');
                redirect('/account/orders');
            }
            try {
                shop_online_begin($app, (string) $order['number']);
            } catch (ShopException $e) {
                flash('error', $e->getMessage());
                redirect('/account/orders');
            } catch (Throwable $e) {
                log_line($app->root, 'shop repay ' . $e->getMessage());
                flash('error', 'اتصال به درگاه برقرار نشد.');
                redirect('/account/orders');
            }
        });
    }
};
