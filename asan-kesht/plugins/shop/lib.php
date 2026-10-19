<?php

declare(strict_types=1);

final class ShopException extends RuntimeException
{
}

/** @return list<array{title:string,items:array<string,array{title:string,text:string}>}> */
function shop_feature_groups(): array
{
    return [
        [
            'title' => 'نمایش در کاتالوگ',
            'items' => [
                'shop-prices' => [
                    'title' => 'نمایش قیمت',
                    'text' => 'قیمت ریالی روی کاتالوگ و صفحه اصلی. تا وقتی قیمت صفر است، به‌جای مبلغ «تماس بگیرید» دیده می‌شود و به سبد اضافه نمی‌شود.',
                ],
                'shop-stock' => [
                    'title' => 'موجودی',
                    'text' => 'اگر کنترل موجودی روشن باشد و تعداد صفر، مدل ناموجود است و فروخته نمی‌شود.',
                ],
            ],
        ],
        [
            'title' => 'سبد و سفارش',
            'items' => [
                'shop-cart' => [
                    'title' => 'سبد خرید',
                    'text' => 'دکمه افزودن به سبد و صفحه سبد.',
                ],
                'shop-checkout' => [
                    'title' => 'ثبت سفارش',
                    'text' => 'تسویه سبد، ساخت سفارش مشتری و تغییر وضعیت از مدیریت.',
                ],
                'shop-guest' => [
                    'title' => 'خرید مهمان',
                    'text' => 'سفارش بدون ورود. اگر خاموش باشد فقط مشتری واردشده، یا مدیر برای آزمایش، می‌تواند سفارش بدهد.',
                ],
            ],
        ],
        [
            'title' => 'روش پرداخت',
            'items' => [
                'shop-pay-cash' => [
                    'title' => 'نقد هنگام تحویل',
                    'text' => 'پرداخت نقدی. درگاه بانکی در این فروشگاه نیست.',
                ],
                'shop-pay-transfer' => [
                    'title' => 'کارت به کارت',
                    'text' => 'واریز به حسابی که شرکت جداگانه اعلام می‌کند.',
                ],
                'shop-pay-check' => [
                    'title' => 'چک',
                    'text' => 'ثبت پرداخت با چک. وصول چک نمایندگان در سامانه نمایندگی است، نه اینجا.',
                ],
            ],
        ],
        [
            'title' => 'ارسال و مالیات',
            'items' => [
                'shop-shipping' => [
                    'title' => 'هزینه ارسال',
                    'text' => 'یک مبلغ ثابت برای هر سفارش. آستانه صفر یعنی ارسال رایگان خودکار نیست.',
                ],
                'shop-tax' => [
                    'title' => 'مالیات',
                    'text' => 'درصد صحیح روی جمع کالا. می‌تواند هزینه ارسال را هم در بر بگیرد.',
                ],
            ],
        ],
    ];
}

/** @return list<string> */
function shop_feature_ids(): array
{
    $ids = [];
    foreach (shop_feature_groups() as $group) {
        foreach ($group['items'] as $id => $_meta) {
            $ids[] = $id;
        }
    }
    return $ids;
}

function shop_ready(App $app, string $id): bool
{
    return $app->pluginEnabled('shop') && $app->pluginEnabled($id);
}

function shop_flag(App $app, string $plugin, string $key, string $default = '0'): bool
{
    return setting($app, $plugin, $key, $default) === '1';
}

function shop_int(App $app, string $plugin, string $key, int $default, int $min, int $max): int
{
    $raw = setting($app, $plugin, $key, (string) $default);
    if (preg_match('/^\d+$/', $raw) !== 1) {
        return $default;
    }
    return max($min, min($max, (int) $raw));
}

function shop_money_setting(App $app, string $plugin, string $key, int $default): int
{
    $raw = setting($app, $plugin, $key, (string) $default);
    if (preg_match('/^\d{1,15}$/', $raw) !== 1) {
        return $default;
    }
    return (int) $raw;
}

function shop_money_input(string $raw): int
{
    $digits = preg_replace('/\D+/', '', en_digits($raw)) ?? '';
    if ($digits === '') {
        return 0;
    }
    if (strlen($digits) > 15) {
        $digits = substr($digits, 0, 15);
    }
    return (int) $digits;
}

/** @param array<string, string> $pairs */
function shop_seed(Database $db, string $plugin, array $pairs): void
{
    foreach ($pairs as $key => $value) {
        $db->run(
            'INSERT INTO ak_settings (plugin, skey, svalue) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE skey = skey',
            [$plugin, $key, $value]
        );
    }
}

/** @return array<int, int> */
function shop_cart_get(): array
{
    $cart = $_SESSION['ak_cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }
    $out = [];
    foreach ($cart as $id => $qty) {
        $machineId = (int) $id;
        $count = (int) $qty;
        if ($machineId > 0 && $count > 0) {
            $out[$machineId] = $count;
        }
    }
    return $out;
}

/** @param array<int, int> $cart */
function shop_cart_set(array $cart): void
{
    $_SESSION['ak_cart'] = $cart;
}

function shop_cart_count(): int
{
    return array_sum(shop_cart_get());
}

function shop_unit_price(App $app, int $machineId): int
{
    if (!shop_ready($app, 'shop-prices')) {
        return 0;
    }
    $row = $app->db->one('SELECT price FROM ak_shop_prices WHERE machine_id = ?', [$machineId]);
    return $row ? max(0, (int) $row['price']) : 0;
}

/** @return array{track:bool,qty:int,oversell:bool,sellable:bool,label:string} */
function shop_stock_state(App $app, int $machineId): array
{
    if (!shop_ready($app, 'shop-stock')) {
        return ['track' => false, 'qty' => 0, 'oversell' => false, 'sellable' => true, 'label' => ''];
    }
    $row = $app->db->one('SELECT qty, track FROM ak_shop_stock WHERE machine_id = ?', [$machineId]);
    $track = !$row || (int) $row['track'] === 1;
    $qty = $row ? (int) $row['qty'] : 0;
    $oversell = shop_flag($app, 'shop-stock', 'allow_oversell', '0');
    $sellable = !$track || $oversell || $qty > 0;
    $showQty = shop_flag($app, 'shop-stock', 'show_qty', '1');
    if (!$track) {
        $label = 'موجود';
    } elseif ($qty > 0) {
        $label = $showQty ? 'موجود: ' . fa_digits((string) $qty) : 'موجود';
    } elseif ($oversell) {
        $label = 'قابل سفارش';
    } else {
        $label = 'ناموجود';
    }
    return ['track' => $track, 'qty' => $qty, 'oversell' => $oversell, 'sellable' => $sellable, 'label' => $label];
}

function shop_price_html(App $app, int $machineId): string
{
    if (!shop_ready($app, 'shop-prices')) {
        return '';
    }
    $row = $app->db->one('SELECT price, compare_at FROM ak_shop_prices WHERE machine_id = ?', [$machineId]);
    $price = $row ? (int) $row['price'] : 0;
    $compare = $row ? (int) $row['compare_at'] : 0;
    if ($price < 1) {
        $text = shop_flag($app, 'shop-prices', 'call_for_zero', '1') ? 'تماس بگیرید' : money(0);
        return '<p class="price">' . e($text) . '</p>';
    }
    $html = '<p class="price">' . e(money($price));
    if ($compare > $price && shop_flag($app, 'shop-prices', 'show_compare', '1')) {
        $html .= ' <s>' . e(money($compare)) . '</s>';
    }
    return $html . '</p>';
}

function shop_stock_html(App $app, int $machineId): string
{
    $state = shop_stock_state($app, $machineId);
    if ($state['label'] === '') {
        return '';
    }
    $class = $state['sellable'] ? 'stock' : 'stock is-out';
    return '<p class="' . $class . '">' . e($state['label']) . '</p>';
}

function shop_line_ok(App $app, array $machine, int $qty): ?string
{
    if ((int) ($machine['active'] ?? 0) !== 1) {
        return 'این مدل در کاتالوگ فعال نیست.';
    }
    if (!shop_ready($app, 'shop-prices')) {
        return 'نمایش قیمت خاموش است.';
    }
    if (shop_unit_price($app, (int) $machine['id']) < 1) {
        return 'برای این مدل هنوز قیمت فروشگاه ثبت نشده.';
    }
    $max = shop_int($app, 'shop-cart', 'max_qty', 20, 1, 99);
    if ($qty < 1 || $qty > $max) {
        return 'تعداد هر مدل از ۱ تا ' . fa_digits((string) $max) . ' است.';
    }
    $stock = shop_stock_state($app, (int) $machine['id']);
    if (!$stock['sellable']) {
        return 'این مدل ناموجود است.';
    }
    if ($stock['track'] && !$stock['oversell'] && $qty > $stock['qty']) {
        return 'تعداد بیشتر از موجودی است.';
    }
    return null;
}

function shop_cart_add(App $app, int $machineId, int $qty): void
{
    if (!shop_ready($app, 'shop-cart')) {
        throw new ShopException('سبد خرید خاموش است.');
    }
    $machine = $app->db->one('SELECT * FROM ak_machines WHERE id = ?', [$machineId]);
    if (!$machine) {
        throw new ShopException('مدل پیدا نشد.');
    }
    $cart = shop_cart_get();
    $next = ($cart[$machineId] ?? 0) + max(1, $qty);
    $error = shop_line_ok($app, $machine, $next);
    if ($error !== null) {
        throw new ShopException($error);
    }
    $cart[$machineId] = $next;
    shop_cart_set($cart);
}

/**
 * @return list<array{machine:array,qty:int,price:int,line:int}>
 */
function shop_cart_lines(App $app): array
{
    $lines = [];
    foreach (shop_cart_get() as $machineId => $qty) {
        $machine = $app->db->one('SELECT * FROM ak_machines WHERE id = ? AND active = 1', [$machineId]);
        if (!$machine) {
            continue;
        }
        $price = shop_unit_price($app, $machineId);
        $lines[] = [
            'machine' => $machine,
            'qty' => $qty,
            'price' => $price,
            'line' => $price * $qty,
        ];
    }
    return $lines;
}

/** @return array{subtotal:int,shipping:int,tax:int,total:int} */
function shop_totals(App $app, int $subtotal): array
{
    $shipping = 0;
    if (shop_ready($app, 'shop-shipping')) {
        $fee = shop_money_setting($app, 'shop-shipping', 'amount', 0);
        $freeOver = shop_money_setting($app, 'shop-shipping', 'free_over', 0);
        $shipping = ($freeOver > 0 && $subtotal >= $freeOver) ? 0 : $fee;
    }
    $tax = 0;
    if (shop_ready($app, 'shop-tax')) {
        $percent = shop_int($app, 'shop-tax', 'percent', 10, 0, 100);
        $base = $subtotal + (shop_flag($app, 'shop-tax', 'on_shipping', '0') ? $shipping : 0);
        $tax = intdiv($base * $percent, 100);
    }
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $subtotal + $shipping + $tax,
    ];
}

/** @return array<string, string> */
function shop_payment_methods(App $app): array
{
    $all = [
        'cash' => ['id' => 'shop-pay-cash', 'label' => 'نقد هنگام تحویل'],
        'transfer' => ['id' => 'shop-pay-transfer', 'label' => 'کارت به کارت'],
        'check' => ['id' => 'shop-pay-check', 'label' => 'چک'],
    ];
    $out = [];
    foreach ($all as $key => $meta) {
        if (shop_ready($app, $meta['id'])) {
            $out[$key] = $meta['label'];
        }
    }
    return $out;
}

/** @return array<string, string> */
function shop_order_statuses(): array
{
    return [
        'submitted' => 'ثبت شد',
        'confirmed' => 'تأیید شد',
        'preparing' => 'آماده‌سازی',
        'shipped' => 'ارسال شد',
        'delivered' => 'تحویل شد',
        'cancelled' => 'لغو شد',
    ];
}

/** @return array{ok:bool,user:?array,message:string} */
function shop_checkout_access(App $app): array
{
    $user = $app->auth->user();
    if (shop_ready($app, 'shop-guest')) {
        return ['ok' => true, 'user' => $user, 'message' => ''];
    }
    if (!$user) {
        return ['ok' => false, 'user' => null, 'message' => 'خرید مهمان خاموش است. با حساب مشتری وارد شوید.'];
    }
    if (!in_array((string) $user['role'], ['customer', 'admin'], true)) {
        return ['ok' => false, 'user' => $user, 'message' => 'ثبت سفارش فروشگاه برای مشتری است. نمایندگان از پنل نمایندگی سفارش می‌دهند.'];
    }
    return ['ok' => true, 'user' => $user, 'message' => ''];
}

function shop_place_order(App $app, ?array $buyer): string
{
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        throw new ShopException('سفارش ثبت نشد.');
    }
    if (!shop_ready($app, 'shop-checkout') || !shop_ready($app, 'shop-cart')) {
        throw new ShopException('ثبت سفارش یا سبد خرید خاموش است.');
    }
    $lines = shop_cart_lines($app);
    if ($lines === []) {
        throw new ShopException('سبد خالی است.');
    }
    foreach ($lines as $line) {
        $error = shop_line_ok($app, $line['machine'], $line['qty']);
        if ($error !== null) {
            throw new ShopException((string) $line['machine']['name'] . ': ' . $error);
        }
    }
    $methods = shop_payment_methods($app);
    $method = post_string('payment', 20);
    if (!isset($methods[$method])) {
        throw new ShopException('روش پرداخت را انتخاب کنید.');
    }
    $name = post_string('name', 120);
    $phone = normalize_phone(post_string('phone', 20));
    $province = post_string('province', 80);
    $city = post_string('city', 80);
    $address = post_string('address', 300);
    $note = post_string('note', 500);
    if (mb_strlen($name) < 3 || !is_mobile($phone) || $province === '' || $city === '' || mb_strlen($address) < 5) {
        throw new ShopException('نام، موبایل، استان، شهر و نشانی را کامل بنویسید.');
    }
    $subtotal = 0;
    foreach ($lines as $line) {
        $subtotal += $line['line'];
    }
    $totals = shop_totals($app, $subtotal);
    $min = shop_money_setting($app, 'shop-checkout', 'min_total', 0);
    if ($min > 0 && $totals['total'] < $min) {
        throw new ShopException('حداقل مبلغ سفارش ' . money($min) . ' است.');
    }
    $userId = $buyer ? (int) $buyer['id'] : null;
    $stockOn = shop_ready($app, 'shop-stock');
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    if ($ip !== '') {
        $recent = $app->db->one(
            'SELECT COUNT(*) AS c FROM ak_shop_orders WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            [$ip]
        );
        if ((int) ($recent['c'] ?? 0) >= 8) {
            throw new ShopException('تعداد سفارش از این شبکه در این ساعت زیاد است.');
        }
    }
    $number = $app->db->transaction(function (Database $db) use ($app, $lines, $totals, $method, $name, $phone, $province, $city, $address, $note, $userId, $stockOn, $ip): string {
        $number = shop_new_number($db);
        $db->run(
            'INSERT INTO ak_shop_orders (number, user_id, name, phone, province, city, address, note, status, payment_method, subtotal, shipping, tax, total, stock_applied, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "submitted", ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $number,
                $userId,
                $name,
                $phone,
                $province,
                $city,
                $address,
                $note,
                $method,
                $totals['subtotal'],
                $totals['shipping'],
                $totals['tax'],
                $totals['total'],
                $stockOn ? 1 : 0,
                $ip,
            ]
        );
        $orderId = (int) $db->id();
        foreach ($lines as $line) {
            $machine = $line['machine'];
            $machineId = (int) $machine['id'];
            $db->run(
                'INSERT INTO ak_shop_order_items (order_id, machine_id, code, name, qty, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$orderId, $machineId, (string) $machine['code'], (string) $machine['name'], $line['qty'], $line['price'], $line['line']]
            );
            if (!$stockOn) {
                continue;
            }
            $row = $db->one('SELECT qty, track FROM ak_shop_stock WHERE machine_id = ? FOR UPDATE', [$machineId]);
            $track = !$row || (int) $row['track'] === 1;
            if (!$track) {
                continue;
            }
            $have = $row ? (int) $row['qty'] : 0;
            $oversell = shop_flag($app, 'shop-stock', 'allow_oversell', '0');
            if (!$oversell && $have < $line['qty']) {
                throw new ShopException('موجودی «' . (string) $machine['name'] . '» کافی نیست.');
            }
            if ($row) {
                $db->run('UPDATE ak_shop_stock SET qty = qty - ? WHERE machine_id = ?', [$line['qty'], $machineId]);
            } else {
                $db->run('INSERT INTO ak_shop_stock (machine_id, qty, track) VALUES (?, ?, 1)', [$machineId, -$line['qty']]);
            }
        }
        return $number;
    });
    shop_cart_set([]);
    $_SESSION['ak_shop_thanks'] = [
        'number' => $number,
        'total' => $totals['total'],
        'payment' => $method,
    ];
    return $number;
}

function shop_new_number(Database $db): string
{
    for ($try = 0; $try < 6; $try++) {
        $number = 'AKS-' . date('ymd') . '-' . (string) random_int(100, 999);
        $exists = $db->one('SELECT id FROM ak_shop_orders WHERE number = ?', [$number]);
        if (!$exists) {
            return $number;
        }
    }
    throw new ShopException('شماره سفارش ساخته نشد. دوباره تلاش کنید.');
}

function shop_set_order_status(App $app, int $id, string $status): void
{
    if (!isset(shop_order_statuses()[$status])) {
        throw new ShopException('وضعیت نامعتبر است.');
    }
    $app->db->transaction(function (Database $db) use ($id, $status): void {
        $order = $db->one('SELECT * FROM ak_shop_orders WHERE id = ? FOR UPDATE', [$id]);
        if (!$order) {
            throw new ShopException('سفارش پیدا نشد.');
        }
        if ((string) $order['status'] === $status) {
            return;
        }
        if ($status === 'cancelled' && (int) $order['stock_applied'] === 1) {
            $items = $db->all('SELECT machine_id, qty FROM ak_shop_order_items WHERE order_id = ?', [$id]);
            foreach ($items as $item) {
                $db->run(
                    'UPDATE ak_shop_stock SET qty = qty + ? WHERE machine_id = ? AND track = 1',
                    [(int) $item['qty'], (int) $item['machine_id']]
                );
            }
            $db->run('UPDATE ak_shop_orders SET status = ?, stock_applied = 0 WHERE id = ?', [$status, $id]);
            return;
        }
        $db->run('UPDATE ak_shop_orders SET status = ? WHERE id = ?', [$status, $id]);
    });
}

/**
 * @return list<array<string, mixed>>
 */
function shop_orders(App $app, string $where, array $params, int $limit): array
{
    $limit = max(1, min(100, $limit));
    $orders = $app->db->all(
        'SELECT * FROM ak_shop_orders WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $limit,
        $params
    );
    if ($orders === []) {
        return [];
    }
    $ids = [];
    foreach ($orders as $order) {
        $ids[] = (int) $order['id'];
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $items = $app->db->all(
        'SELECT * FROM ak_shop_order_items WHERE order_id IN (' . $marks . ') ORDER BY id',
        $ids
    );
    $grouped = [];
    foreach ($items as $item) {
        $grouped[(int) $item['order_id']][] = $item;
    }
    foreach ($orders as $index => $order) {
        $orders[$index]['items'] = $grouped[(int) $order['id']] ?? [];
    }
    return $orders;
}

function shop_save(App $app): void
{
    $posted = $_POST['plugins'] ?? [];
    $wanted = is_array($posted) ? $posted : [];
    foreach (shop_feature_ids() as $id) {
        $app->db->run(
            'UPDATE ak_plugins SET enabled = ? WHERE id = ?',
            [in_array($id, $wanted, true) ? 1 : 0, $id]
        );
    }
    save_setting($app, 'shop-prices', 'call_for_zero', isset($_POST['call_for_zero']) ? '1' : '0');
    save_setting($app, 'shop-prices', 'show_compare', isset($_POST['show_compare']) ? '1' : '0');
    save_setting($app, 'shop-stock', 'show_qty', isset($_POST['show_qty']) ? '1' : '0');
    save_setting($app, 'shop-stock', 'allow_oversell', isset($_POST['allow_oversell']) ? '1' : '0');
    $maxQty = (int) post_string('max_qty', 3);
    save_setting($app, 'shop-cart', 'max_qty', (string) max(1, min(99, $maxQty > 0 ? $maxQty : 20)));
    save_setting($app, 'shop-checkout', 'min_total', (string) shop_money_input(post_string('min_total', 20)));
    save_setting($app, 'shop-shipping', 'amount', (string) shop_money_input(post_string('shipping_amount', 20)));
    save_setting($app, 'shop-shipping', 'free_over', (string) shop_money_input(post_string('free_over', 20)));
    $percent = (int) post_string('tax_percent', 3);
    save_setting($app, 'shop-tax', 'percent', (string) max(0, min(100, $percent)));
    save_setting($app, 'shop-tax', 'on_shipping', isset($_POST['on_shipping']) ? '1' : '0');
    save_setting($app, 'shop-pay-transfer', 'transfer_note', post_string('transfer_note', 400));
    $machines = shop_machines($app);
    $prices = $_POST['price'] ?? [];
    $compares = $_POST['compare'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $tracks = $_POST['track'] ?? [];
    if (!is_array($prices) || !is_array($compares) || !is_array($qtys) || !is_array($tracks)) {
        return;
    }
    foreach ($machines as $machine) {
        $id = (int) $machine['id'];
        $key = (string) $id;
        $app->db->run(
            'INSERT INTO ak_shop_prices (machine_id, price, compare_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE price = VALUES(price), compare_at = VALUES(compare_at)',
            [$id, shop_money_input((string) ($prices[$key] ?? '0')), shop_money_input((string) ($compares[$key] ?? '0'))]
        );
        $qty = (int) preg_replace('/\D+/', '', en_digits((string) ($qtys[$key] ?? '0')));
        $qty = max(0, min(100000, $qty));
        $app->db->run(
            'INSERT INTO ak_shop_stock (machine_id, qty, track) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE qty = VALUES(qty), track = VALUES(track)',
            [$id, $qty, isset($tracks[$key]) ? 1 : 0]
        );
    }
}

/** @return list<array<string, mixed>> */
function shop_machines(App $app): array
{
    try {
        return $app->db->all('SELECT id, code, name, line FROM ak_machines ORDER BY sort_order, id');
    } catch (Throwable) {
        return [];
    }
}
