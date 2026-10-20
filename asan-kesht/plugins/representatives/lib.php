<?php

declare(strict_types=1);

function dealer_install(Database $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_regions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            manager_user_id INT UNSIGNED NULL,
            UNIQUE KEY name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            code VARCHAR(20) NOT NULL,
            kind VARCHAR(20) NOT NULL,
            name VARCHAR(160) NOT NULL,
            national_id VARCHAR(30) NOT NULL DEFAULT "",
            phone VARCHAR(20) NOT NULL DEFAULT "",
            province VARCHAR(80) NOT NULL DEFAULT "",
            city VARCHAR(80) NOT NULL DEFAULT "",
            cooperation VARCHAR(20) NOT NULL,
            sale_model VARCHAR(20) NOT NULL,
            territory VARCHAR(240) NOT NULL DEFAULT "",
            product_groups VARCHAR(120) NOT NULL DEFAULT "",
            exclusive_city TINYINT(1) NOT NULL DEFAULT 0,
            exclusive_exception TINYINT(1) NOT NULL DEFAULT 0,
            level VARCHAR(40) NOT NULL DEFAULT "",
            credit_limit BIGINT NOT NULL DEFAULT 0,
            credit_days INT UNSIGNED NOT NULL DEFAULT 30,
            settlement VARCHAR(240) NOT NULL DEFAULT "",
            contract_start DATE NULL,
            contract_end DATE NULL,
            region_id INT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY code (code),
            UNIQUE KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_prices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL DEFAULT 0,
            level VARCHAR(40) NOT NULL DEFAULT "",
            machine_id INT UNSIGNED NOT NULL,
            price BIGINT NOT NULL,
            cost BIGINT NOT NULL DEFAULT 0,
            UNIQUE KEY price_key (dealer_id, level, machine_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            number VARCHAR(30) NOT NULL,
            dealer_id INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            pay_mode VARCHAR(20) NOT NULL,
            cash_amount BIGINT NOT NULL DEFAULT 0,
            check_amount BIGINT NOT NULL DEFAULT 0,
            credit_amount BIGINT NOT NULL DEFAULT 0,
            credit_days INT UNSIGNED NOT NULL DEFAULT 0,
            total BIGINT NOT NULL DEFAULT 0,
            note VARCHAR(500) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            UNIQUE KEY number (number),
            INDEX (dealer_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            machine_id INT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            qty INT UNSIGNED NOT NULL,
            unit_price BIGINT NOT NULL,
            unit_cost BIGINT NOT NULL DEFAULT 0,
            INDEX (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_ledger (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            direction VARCHAR(10) NOT NULL,
            amount BIGINT NOT NULL,
            kind VARCHAR(30) NOT NULL,
            ref_type VARCHAR(30) NOT NULL DEFAULT "",
            ref_id INT UNSIGNED NOT NULL DEFAULT 0,
            note VARCHAR(240) NOT NULL DEFAULT "",
            created_by INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX (dealer_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_checks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NULL,
            amount BIGINT NOT NULL,
            bank VARCHAR(80) NOT NULL,
            serial VARCHAR(40) NOT NULL,
            due_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL,
            image VARCHAR(80) NOT NULL DEFAULT "",
            note VARCHAR(240) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            INDEX (dealer_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NULL,
            amount BIGINT NOT NULL,
            method VARCHAR(20) NOT NULL,
            note VARCHAR(240) NOT NULL DEFAULT "",
            receipt VARCHAR(80) NOT NULL DEFAULT "",
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX (dealer_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_commission_rules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL DEFAULT 0,
            level VARCHAR(40) NOT NULL DEFAULT "",
            line VARCHAR(20) NOT NULL DEFAULT "",
            percent TINYINT UNSIGNED NOT NULL,
            basis VARCHAR(20) NOT NULL,
            settle_on VARCHAR(20) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_commissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NOT NULL,
            amount BIGINT NOT NULL,
            status VARCHAR(20) NOT NULL,
            basis VARCHAR(20) NOT NULL,
            settle_on VARCHAR(20) NOT NULL,
            note VARCHAR(240) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            INDEX (order_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_circulars (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            kind VARCHAR(20) NOT NULL,
            priority VARCHAR(20) NOT NULL,
            audience VARCHAR(20) NOT NULL,
            province VARCHAR(80) NOT NULL DEFAULT "",
            due_date DATE NULL,
            created_by INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_circular_receipts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            circular_id INT UNSIGNED NOT NULL,
            dealer_id INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            report TEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY circular_dealer (circular_id, dealer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            order_id INT UNSIGNED NULL,
            amount BIGINT NOT NULL DEFAULT 0,
            until_date DATE NULL,
            body TEXT NOT NULL,
            step TINYINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL,
            response VARCHAR(500) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX (status, type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_disputes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            ledger_id INT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            reply TEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_dealer_files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dealer_id INT UNSIGNED NOT NULL,
            kind VARCHAR(30) NOT NULL,
            title VARCHAR(160) NOT NULL,
            filename VARCHAR(80) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX (dealer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $defaults = [
        'enforce_exclusive' => '1',
        'allow_resale' => '1',
        'allow_commission' => '1',
        'default_credit_days' => '30',
        'default_commission_percent' => '5',
        'commission_basis' => 'sale',
        'commission_settle' => 'collection',
        'return_days' => '7',
    ];
    foreach ($defaults as $key => $value) {
        $db->run(
            'INSERT INTO ak_settings (plugin, skey, svalue) VALUES ("representatives", ?, ?)
             ON DUPLICATE KEY UPDATE skey = skey',
            [$key, $value]
        );
    }
}

/** @return array<string, string> */
function dealer_lines(): array
{
    return [
        'tillage' => 'خاک‌ورزی',
        'seeding' => 'کاشت',
        'care' => 'داشت',
        'harvest' => 'برداشت و حمل',
    ];
}

/** @return array<string, string> */
function dealer_statuses(): array
{
    return [
        'pending' => 'در انتظار تأیید',
        'active' => 'فعال',
        'suspended' => 'تعلیق',
        'ended' => 'پایان قرارداد',
    ];
}

/** @return array<string, string> */
function dealer_cooperations(): array
{
    return [
        'sale' => 'فروش',
        'service' => 'خدمات',
        'repair' => 'تعمیرات',
        'mixed' => 'ترکیبی',
    ];
}

/** @return array<string, string> */
function dealer_sale_models(): array
{
    return [
        'resale' => 'خرید از شرکت و فروش دوباره',
        'commission' => 'فروش برای شرکت و پورسانت',
    ];
}

function dealer_commission_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'در جریان',
        'earned' => 'قطعی',
        'reversed' => 'برگشت‌خورده',
        default => $status,
    };
}

function dealer_basis_label(string $basis): string
{
    return $basis === 'gross' ? 'سود ناخالص' : 'مبلغ فروش';
}

function dealer_settle_label(string $settle): string
{
    return $settle === 'sale' ? 'هنگام تأیید فروش' : 'هنگام وصول وجه';
}

/** @return array<string, string> */
function dealer_order_statuses(): array
{
    return [
        'submitted' => 'در انتظار تأیید',
        'approved' => 'تأیید شده',
        'preparing' => 'آماده‌سازی',
        'shipped' => 'ارسال شده',
        'delivered' => 'تحویل شده',
        'cancelled' => 'لغو شده',
    ];
}

/** @return array<string, string> */
function dealer_request_types(): array
{
    return [
        'credit_limit' => 'افزایش سقف اعتبار',
        'discount' => 'تخفیف ویژه معامله',
        'payment_extend' => 'تمدید مهلت پرداخت',
        'return_goods' => 'مرجوعی یا تعویض',
        'warranty' => 'گارانتی و خدمات فنی',
        'promo' => 'اقلام تبلیغاتی',
        'contract' => 'تمدید یا تغییر قرارداد',
    ];
}

/** @return list<string> */
function dealer_request_flow(string $type): array
{
    return match ($type) {
        'credit_limit', 'payment_extend' => ['accountant', 'admin'],
        'discount', 'contract' => ['admin'],
        'return_goods', 'promo' => ['employee', 'admin'],
        'warranty' => ['employee'],
        default => ['admin'],
    };
}

function dealer_setting(App $app, string $key, string $default = ''): string
{
    return setting($app, 'representatives', $key, $default);
}

function dealer_money_input(string $key): int
{
    $digits = preg_replace('/\D+/', '', post_string($key, 18)) ?? '';
    return $digits === '' ? 0 : (int) $digits;
}

function dealer_date_input(string $key): ?string
{
    $value = post_string($key, 10);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

/** @return array{sql:string,params:list<int>} */
function dealer_scope(array $user): array
{
    if (($user['role'] ?? '') === 'region_manager') {
        return [
            'sql' => ' AND d.region_id IN (SELECT id FROM ak_dealer_regions WHERE manager_user_id = ?)',
            'params' => [(int) $user['id']],
        ];
    }
    return ['sql' => '', 'params' => []];
}

function dealer_require_company(App $app): array
{
    return require_role($app, ['admin', 'employee', 'accountant', 'region_manager']);
}

function dealer_require_editor(App $app): array
{
    return require_role($app, ['admin', 'employee']);
}

function dealer_require_finance(App $app): array
{
    return require_role($app, ['admin', 'accountant']);
}

function dealer_by_user(App $app, int $userId): ?array
{
    return $app->db->one('SELECT * FROM ak_dealers WHERE user_id = ?', [$userId]);
}

function dealer_require_rep(App $app): array
{
    $user = require_role($app, ['representative']);
    $dealer = dealer_by_user($app, (int) $user['id']);
    if (!$dealer) {
        http_response_code(403);
        echo 'پرونده نمایندگی به این کاربر وصل نیست.';
        exit;
    }
    return $dealer;
}

function dealer_find(App $app, int $id, array $user): ?array
{
    $scope = dealer_scope($user);
    return $app->db->one('SELECT d.* FROM ak_dealers d WHERE d.id = ?' . $scope['sql'], array_merge([$id], $scope['params']));
}

function dealer_balance(App $app, int $dealerId): int
{
    $row = $app->db->one(
        'SELECT COALESCE(SUM(CASE WHEN direction = "debit" THEN amount ELSE -amount END), 0) AS s
         FROM ak_dealer_ledger WHERE dealer_id = ?',
        [$dealerId]
    );
    return (int) ($row['s'] ?? 0);
}

/** @return array{price:int,cost:int}|null */
function dealer_price_for(App $app, array $dealer, int $machineId): ?array
{
    $row = $app->db->one(
        'SELECT price, cost FROM ak_dealer_prices
         WHERE machine_id = ? AND (dealer_id = ? OR (dealer_id = 0 AND level = ?) OR (dealer_id = 0 AND level = ""))
         ORDER BY (dealer_id = 0) ASC, (level = "") ASC LIMIT 1',
        [$machineId, (int) $dealer['id'], (string) $dealer['level']]
    );
    if (!$row) {
        return null;
    }
    return ['price' => (int) $row['price'], 'cost' => (int) $row['cost']];
}

function dealer_overdue(App $app, array $dealer): int
{
    $checks = $app->db->one(
        'SELECT COALESCE(SUM(amount), 0) AS s FROM ak_dealer_checks
         WHERE dealer_id = ? AND status = "pending" AND due_date < CURDATE()',
        [(int) $dealer['id']]
    );
    $orders = $app->db->all(
        'SELECT id, credit_amount, credit_days, created_at FROM ak_dealer_orders
         WHERE dealer_id = ? AND status <> "cancelled" AND credit_amount > 0
           AND DATE_ADD(created_at, INTERVAL credit_days DAY) < NOW()',
        [(int) $dealer['id']]
    );
    $credit = 0;
    foreach ($orders as $order) {
        $paid = $app->db->one(
            'SELECT COALESCE(SUM(amount), 0) AS s FROM ak_dealer_payments
             WHERE order_id = ? AND status = "confirmed"',
            [(int) $order['id']]
        );
        $gap = (int) $order['credit_amount'] - (int) ($paid['s'] ?? 0);
        if ($gap > 0) {
            $credit += $gap;
        }
    }
    return (int) ($checks['s'] ?? 0) + $credit;
}

function dealer_order_block(App $app, array $dealer, int $creditPortion): ?string
{
    if ($dealer['status'] !== 'active') {
        return 'فقط نماینده فعال می‌تواند سفارش بدهد.';
    }
    $today = date('Y-m-d');
    if (!empty($dealer['contract_end']) && $dealer['contract_end'] < $today) {
        return 'قرارداد این نماینده تمام شده است.';
    }
    if (!empty($dealer['contract_start']) && $dealer['contract_start'] > $today) {
        return 'تاریخ شروع قرارداد هنوز نرسیده است.';
    }
    if (dealer_overdue($app, $dealer) > 0 && $creditPortion > 0) {
        return 'بدهی سررسیدشده یا چک برگشتی‌نشده وجود دارد. سفارش نسیه تا تسویه تأیید نمی‌شود.';
    }
    $next = dealer_balance($app, (int) $dealer['id']) + $creditPortion;
    if ($creditPortion > 0 && $next > (int) $dealer['credit_limit']) {
        return 'این سفارش از سقف اعتبار نماینده بیشتر می‌شود.';
    }
    return null;
}

function dealer_post(App $app, int $dealerId, string $direction, int $amount, string $kind, string $refType, int $refId, string $note, int $userId): void
{
    if ($amount < 1 || !in_array($direction, ['debit', 'credit'], true)) {
        return;
    }
    $app->db->run(
        'INSERT INTO ak_dealer_ledger (dealer_id, direction, amount, kind, ref_type, ref_id, note, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [$dealerId, $direction, $amount, $kind, $refType, $refId, mb_substr($note, 0, 240), $userId]
    );
}

function dealer_has_ledger(App $app, string $kind, string $refType, int $refId): bool
{
    $row = $app->db->one(
        'SELECT id FROM ak_dealer_ledger WHERE kind = ? AND ref_type = ? AND ref_id = ? LIMIT 1',
        [$kind, $refType, $refId]
    );
    return $row !== null;
}

/** @return array{0:int,1:int,2:int} */
function dealer_split(int $total, string $mode, int $cashPct, int $checkPct, int $creditPct): array
{
    if ($mode === 'cash') {
        return [$total, 0, 0];
    }
    if ($mode === 'check') {
        return [0, $total, 0];
    }
    if ($mode === 'credit') {
        return [0, 0, $total];
    }
    $cash = intdiv($total * $cashPct, 100);
    $check = intdiv($total * $checkPct, 100);
    $credit = $total - $cash - $check;
    return [$cash, $check, max(0, $credit)];
}

function dealer_store_upload(App $app, string $field): string
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('بارگذاری فایل ناموفق بود.');
    }
    if ((int) ($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('حجم فایل باید حداکثر ۴ مگابایت باشد.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        default => '',
    };
    if ($ext === '') {
        throw new RuntimeException('فقط JPG، PNG، WEBP یا PDF مجاز است.');
    }
    $dir = $app->root . '/storage/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('پوشه پرونده ساخته نشد.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('ذخیره فایل ناموفق بود.');
    }
    return $name;
}

function dealer_send_file(App $app, string $name): void
{
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/', $name)) {
        http_response_code(404);
        exit;
    }
    $path = $app->root . '/storage/uploads/' . $name;
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    readfile($path);
    exit;
}

function dealer_rule_for(App $app, array $dealer, string $line): array
{
    $rule = $app->db->one(
        'SELECT * FROM ak_commission_rules
         WHERE (dealer_id = ? OR dealer_id = 0) AND (level = ? OR level = "") AND (line = ? OR line = "")
         ORDER BY (dealer_id = 0) ASC, (level = "") ASC, (line = "") ASC, id DESC LIMIT 1',
        [(int) $dealer['id'], (string) $dealer['level'], $line]
    );
    if ($rule) {
        return $rule;
    }
    return [
        'dealer_id' => 0,
        'level' => '',
        'line' => '',
        'percent' => (int) dealer_setting($app, 'default_commission_percent', '5'),
        'basis' => dealer_setting($app, 'commission_basis', 'sale'),
        'settle_on' => dealer_setting($app, 'commission_settle', 'collection'),
    ];
}

function dealer_general_rule(App $app, array $dealer): array
{
    $rule = $app->db->one(
        'SELECT * FROM ak_commission_rules
         WHERE (dealer_id = ? OR dealer_id = 0) AND (level = ? OR level = "") AND line = ""
         ORDER BY (dealer_id = 0) ASC, (level = "") ASC, id DESC LIMIT 1',
        [(int) $dealer['id'], (string) ($dealer['level'] ?? '')]
    );
    if ($rule) {
        return $rule;
    }
    return [
        'dealer_id' => 0,
        'level' => '',
        'line' => '',
        'percent' => (int) dealer_setting($app, 'default_commission_percent', '5'),
        'basis' => dealer_setting($app, 'commission_basis', 'sale'),
        'settle_on' => dealer_setting($app, 'commission_settle', 'collection'),
    ];
}

function dealer_approve(App $app, array $order, int $userId): void
{
    if ($order['status'] !== 'submitted') {
        flash('error', 'فقط سفارش در انتظار تأیید را می‌توان تأیید کرد.');
        redirect('/admin/dealers/orders/' . $order['id']);
    }
    $dealer = $app->db->one('SELECT * FROM ak_dealers WHERE id = ?', [(int) $order['dealer_id']]);
    if (!$dealer) {
        redirect('/admin/dealers/orders');
    }
    $block = dealer_order_block($app, $dealer, (int) $order['credit_amount']);
    if ($block !== null) {
        flash('error', $block);
        redirect('/admin/dealers/orders/' . $order['id']);
    }
    $items = $app->db->all(
        'SELECT i.*, m.line FROM ak_dealer_order_items i JOIN ak_machines m ON m.id = i.machine_id WHERE i.order_id = ?',
        [(int) $order['id']]
    );
    $app->db->transaction(function () use ($app, $order, $dealer, $items, $userId): void {
        if (!dealer_has_ledger($app, 'invoice', 'order', (int) $order['id'])) {
            dealer_post($app, (int) $dealer['id'], 'debit', (int) $order['total'], 'invoice', 'order', (int) $order['id'], 'فاکتور ' . $order['number'], $userId);
        }
        if ($dealer['sale_model'] === 'commission') {
            foreach ($items as $item) {
                $rule = dealer_rule_for($app, $dealer, (string) $item['line']);
                $basis = (string) $rule['basis'] === 'gross' ? 'gross' : 'sale';
                $settle = (string) $rule['settle_on'] === 'sale' ? 'sale' : 'collection';
                $qty = (int) $item['qty'];
                $price = (int) $item['unit_price'];
                $cost = (int) $item['unit_cost'];
                $missing = false;
                if ($basis === 'gross') {
                    if ($cost <= 0) {
                        $missing = true;
                    }
                    $base = $qty * max(0, $price - $cost);
                } else {
                    $base = $qty * $price;
                }
                $amount = intdiv($base * (int) $rule['percent'], 100);
                if ($amount < 1) {
                    continue;
                }
                $note = 'پورسانت ' . $order['number'];
                if ($missing) {
                    $note .= '؛ بهای تمام‌شده ثبت نشده و پایه برابر مبلغ فروش شده است';
                }
                $app->db->run(
                    'INSERT INTO ak_dealer_commissions (dealer_id, order_id, amount, status, basis, settle_on, note, created_at) VALUES (?, ?, ?, "pending", ?, ?, ?, NOW())',
                    [(int) $dealer['id'], (int) $order['id'], $amount, $basis, $settle, $note]
                );
            }
        }
        $app->db->run('UPDATE ak_dealer_orders SET status = "approved" WHERE id = ?', [(int) $order['id']]);
    });
    dealer_earn_due($app, (int) $order['id'], $userId);
    flash('success', 'سفارش تأیید شد و سند بدهکار در حساب نماینده نشست.');
    redirect('/admin/dealers/orders/' . $order['id']);
}

function dealer_reverse_order(App $app, array $order, int $userId): void
{
    if ($order['status'] === 'cancelled') {
        return;
    }
    $app->db->transaction(function () use ($app, $order, $userId): void {
        if (dealer_has_ledger($app, 'invoice', 'order', (int) $order['id']) && !dealer_has_ledger($app, 'reversal', 'order', (int) $order['id'])) {
            dealer_post($app, (int) $order['dealer_id'], 'credit', (int) $order['total'], 'reversal', 'order', (int) $order['id'], 'برگشت فاکتور ' . $order['number'], $userId);
        }
        $rows = $app->db->all('SELECT * FROM ak_dealer_commissions WHERE order_id = ? AND status <> "reversed"', [(int) $order['id']]);
        foreach ($rows as $row) {
            if ($row['status'] === 'earned') {
                dealer_post($app, (int) $row['dealer_id'], 'debit', (int) $row['amount'], 'commission_reversal', 'commission', (int) $row['id'], 'برگشت پورسانت ' . $order['number'], $userId);
            }
            $app->db->run('UPDATE ak_dealer_commissions SET status = "reversed" WHERE id = ?', [(int) $row['id']]);
        }
        $app->db->run('UPDATE ak_dealer_orders SET status = "cancelled" WHERE id = ?', [(int) $order['id']]);
    });
}

function dealer_cancel(App $app, array $order, int $userId): void
{
    dealer_reverse_order($app, $order, $userId);
    flash('success', 'سفارش لغو شد و سند اصلاحی حساب و پورسانت ثبت شد.');
    redirect('/admin/dealers/orders/' . $order['id']);
}

function dealer_earn_due(App $app, int $orderId, int $userId): void
{
    $order = $app->db->one('SELECT * FROM ak_dealer_orders WHERE id = ?', [$orderId]);
    if (!$order || $order['status'] === 'cancelled') {
        return;
    }
    $paid = $app->db->one(
        'SELECT COALESCE(SUM(amount), 0) AS s FROM ak_dealer_payments WHERE order_id = ? AND status = "confirmed"',
        [$orderId]
    );
    $covered = (int) ($paid['s'] ?? 0) >= (int) $order['total'];
    $rows = $app->db->all('SELECT * FROM ak_dealer_commissions WHERE order_id = ? AND status = "pending"', [$orderId]);
    foreach ($rows as $row) {
        if ($row['settle_on'] === 'collection' && !$covered) {
            continue;
        }
        dealer_post($app, (int) $row['dealer_id'], 'credit', (int) $row['amount'], 'commission', 'commission', (int) $row['id'], (string) $row['note'], $userId);
        $app->db->run('UPDATE ak_dealer_commissions SET status = "earned" WHERE id = ?', [(int) $row['id']]);
    }
}

function dealer_apply_request(App $app, array $request): void
{
    if ($request['type'] === 'credit_limit' && (int) $request['amount'] > 0) {
        $app->db->run('UPDATE ak_dealers SET credit_limit = ? WHERE id = ?', [(int) $request['amount'], (int) $request['dealer_id']]);
    }
    if ($request['type'] === 'payment_extend' && (int) $request['amount'] > 0) {
        $app->db->run('UPDATE ak_dealers SET credit_days = credit_days + ? WHERE id = ?', [(int) $request['amount'], (int) $request['dealer_id']]);
    }
    if ($request['type'] === 'contract' && !empty($request['until_date'])) {
        $app->db->run(
            'UPDATE ak_dealers SET contract_end = ?, status = "active" WHERE id = ?',
            [$request['until_date'], (int) $request['dealer_id']]
        );
    }
    if ($request['type'] === 'return_goods' && !empty($request['order_id'])) {
        $order = $app->db->one('SELECT * FROM ak_dealer_orders WHERE id = ? AND dealer_id = ?', [(int) $request['order_id'], (int) $request['dealer_id']]);
        if ($order && $order['status'] !== 'cancelled') {
            $user = $app->auth->user();
            dealer_reverse_order($app, $order, (int) ($user['id'] ?? 0));
        }
    }
}
