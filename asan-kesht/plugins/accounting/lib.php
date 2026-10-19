<?php

declare(strict_types=1);

function accounting_roles(): array
{
    return ['admin', 'employee', 'accountant'];
}

function accounting_schema(Database $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_parties (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(20) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_invoices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            number VARCHAR(40) NOT NULL,
            party_id INT UNSIGNED NOT NULL,
            kind VARCHAR(20) NOT NULL DEFAULT "sale",
            status VARCHAR(20) NOT NULL,
            notes VARCHAR(500) NOT NULL DEFAULT "",
            total BIGINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY number (number),
            INDEX kind_status (kind, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_invoice_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            qty INT UNSIGNED NOT NULL,
            unit_price BIGINT NOT NULL,
            unit_cost BIGINT NOT NULL DEFAULT 0,
            INDEX (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NOT NULL,
            amount BIGINT NOT NULL,
            method VARCHAR(40) NOT NULL,
            note VARCHAR(240) NOT NULL DEFAULT "",
            paid_at DATE NOT NULL,
            INDEX (invoice_id),
            INDEX (paid_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_expenses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            category VARCHAR(80) NOT NULL,
            amount BIGINT NOT NULL,
            spent_at DATE NOT NULL,
            INDEX (spent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_checkbooks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(120) NOT NULL,
            bank VARCHAR(80) NOT NULL,
            start_no INT UNSIGNED NOT NULL,
            end_no INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_checks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            checkbook_id INT UNSIGNED NULL,
            direction VARCHAR(8) NOT NULL,
            number VARCHAR(40) NOT NULL,
            bank VARCHAR(80) NOT NULL DEFAULT "",
            amount BIGINT NOT NULL,
            due_on DATE NOT NULL,
            party_name VARCHAR(160) NOT NULL DEFAULT "",
            status VARCHAR(20) NOT NULL,
            note VARCHAR(240) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            INDEX (direction, status),
            INDEX (due_on)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_payroll (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            person VARCHAR(120) NOT NULL,
            job_title VARCHAR(80) NOT NULL DEFAULT "",
            period CHAR(7) NOT NULL,
            base_pay BIGINT NOT NULL,
            benefit BIGINT NOT NULL DEFAULT 0,
            deduction BIGINT NOT NULL DEFAULT 0,
            paid_on DATE NULL,
            note VARCHAR(240) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            INDEX (period)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_visitors (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(20) NOT NULL DEFAULT "",
            code VARCHAR(16) NOT NULL,
            percent TINYINT UNSIGNED NOT NULL,
            basis VARCHAR(12) NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            UNIQUE KEY code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_visitor_shares (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            visitor_id INT UNSIGNED NOT NULL,
            source VARCHAR(16) NOT NULL,
            ref_id INT UNSIGNED NOT NULL,
            sale_amount BIGINT NOT NULL,
            cost_amount BIGINT NOT NULL,
            percent TINYINT UNSIGNED NOT NULL,
            basis VARCHAR(12) NOT NULL,
            amount BIGINT NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY source_ref (source, ref_id),
            INDEX (visitor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    accounting_add_column($db, 'ak_invoices', 'kind', 'VARCHAR(20) NOT NULL DEFAULT "sale"');
    accounting_add_column($db, 'ak_invoices', 'source', 'VARCHAR(20) NOT NULL DEFAULT "manual"');
    accounting_add_column($db, 'ak_invoices', 'source_id', 'INT UNSIGNED NOT NULL DEFAULT 0');
    accounting_add_column($db, 'ak_invoices', 'visitor_id', 'INT UNSIGNED NOT NULL DEFAULT 0');
    accounting_add_column($db, 'ak_invoice_items', 'unit_cost', 'BIGINT NOT NULL DEFAULT 0');
    accounting_add_column($db, 'ak_payroll', 'employer_share', 'BIGINT NOT NULL DEFAULT 0');
    $ready = true;
}

function accounting_add_column(Database $db, string $table, string $column, string $definition): void
{
    $allowed = [
        'ak_invoices' => ['kind' => true, 'source' => true, 'source_id' => true, 'visitor_id' => true],
        'ak_invoice_items' => ['unit_cost' => true],
        'ak_payroll' => ['employer_share' => true],
    ];
    if (!isset($allowed[$table][$column])) {
        return;
    }
    $row = $db->one(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    );
    if ($row) {
        return;
    }
    $db->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
}

/** @return array<string, array{label: string, prefix: string}> */
function accounting_kinds(): array
{
    return [
        'proforma' => ['label' => 'پیش‌فاکتور', 'prefix' => 'PF'],
        'sale' => ['label' => 'فاکتور فروش', 'prefix' => 'FS'],
        'purchase' => ['label' => 'فاکتور خرید', 'prefix' => 'FB'],
    ];
}

function accounting_kind(string $kind): ?array
{
    return accounting_kinds()[$kind] ?? null;
}

function accounting_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'پیش‌نویس',
        'sent' => 'ارسال‌شده',
        'accepted' => 'پذیرفته',
        'issued' => 'صادر',
        'partial' => 'بخشی',
        'paid' => 'تسویه',
        'cancelled' => 'باطل',
        'pending' => 'در جریان',
        'cleared' => 'وصول یا پاس',
        'bounced' => 'برگشت',
        'void' => 'باطل',
        default => $status,
    };
}

function accounting_amount(string $raw): int
{
    $raw = en_digits(trim($raw));
    if ($raw === '' || !preg_match('/^\d{1,15}$/', $raw)) {
        return 0;
    }
    return (int) $raw;
}

function accounting_date(string $raw): ?string
{
    $raw = en_digits(trim($raw));
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 ? $raw : null;
}

function accounting_party(App $app): int
{
    $existing = (int) post_string('party_id', 12);
    if ($existing > 0) {
        $row = $app->db->one('SELECT id FROM ak_parties WHERE id = ?', [$existing]);
        if ($row) {
            return (int) $row['id'];
        }
    }
    $name = post_string('party_name', 160);
    if ($name === '') {
        return 0;
    }
    $phone = normalize_phone(post_string('party_phone', 20));
    $app->db->run('INSERT INTO ak_parties (name, phone, created_at) VALUES (?, ?, NOW())', [$name, $phone]);
    return (int) $app->db->id();
}

/** @return list<array{title: string, qty: int, unit_price: int, unit_cost: int}> */
function accounting_items(string $kind): array
{
    $titles = $_POST['item_title'] ?? [];
    $qtys = $_POST['item_qty'] ?? [];
    $prices = $_POST['item_price'] ?? [];
    $costs = $_POST['item_cost'] ?? [];
    if (!is_array($titles) || !is_array($qtys) || !is_array($prices)) {
        return [];
    }
    $items = [];
    foreach ($titles as $i => $title) {
        $title = trim((string) $title);
        $qty = (int) en_digits((string) ($qtys[$i] ?? '0'));
        $price = accounting_amount((string) ($prices[$i] ?? '0'));
        $cost = accounting_amount(is_array($costs) ? (string) ($costs[$i] ?? '0') : '0');
        if ($title === '' || $qty < 1 || $price < 0) {
            continue;
        }
        if ($kind === 'purchase') {
            $cost = $price;
        }
        $items[] = [
            'title' => mb_substr($title, 0, 180),
            'qty' => $qty,
            'unit_price' => $price,
            'unit_cost' => $cost,
        ];
    }
    return $items;
}

function accounting_save_document(App $app, string $kind): void
{
    $meta = accounting_kind($kind);
    if ($meta === null) {
        flash('error', 'نوع سند شناخته نشد.');
        redirect('/admin/accounting');
    }
    $partyId = accounting_party($app);
    $items = accounting_items($kind);
    if ($partyId === 0 || $items === []) {
        flash('error', 'طرف‌حساب و حداقل یک ردیف لازم است.');
        redirect('/admin/accounting/' . $kind);
    }
    $total = 0;
    foreach ($items as $item) {
        $total += $item['qty'] * $item['unit_price'];
    }
    $number = accounting_prefix($app, $kind) . '-' . date('ymd') . '-' . random_int(100, 999);
    $status = $kind === 'proforma' ? 'draft' : 'issued';
    $visitorId = 0;
    if ($kind === 'sale') {
        $wanted = (int) post_string('visitor_id', 12);
        if ($wanted > 0 && $app->db->one('SELECT id FROM ak_visitors WHERE id = ? AND active = 1', [$wanted])) {
            $visitorId = $wanted;
        }
    }
    $costSum = 0;
    foreach ($items as $item) {
        $costSum += $item['qty'] * $item['unit_cost'];
    }
    $notes = post_string('notes', 500);
    $invoiceId = (int) $app->db->transaction(function () use ($app, $number, $partyId, $kind, $status, $total, $items, $visitorId, $notes): int {
        $app->db->run(
            'INSERT INTO ak_invoices (number, party_id, kind, status, notes, total, source, source_id, visitor_id, created_at) VALUES (?, ?, ?, ?, ?, ?, "manual", 0, ?, NOW())',
            [$number, $partyId, $kind, $status, $notes, $total, $visitorId]
        );
        $invoiceId = (int) $app->db->id();
        foreach ($items as $item) {
            $app->db->run(
                'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?)',
                [$invoiceId, $item['title'], $item['qty'], $item['unit_price'], $item['unit_cost']]
            );
        }
        return $invoiceId;
    });
    if ($kind === 'sale' && $visitorId > 0) {
        accounting_record_visitor_share($app, $visitorId, 'invoice', $invoiceId, $total, $costSum);
    }
    ak_audit($app, $meta['label'], $number, 'مبلغ ' . (string) $total . ($visitorId > 0 ? '، با ویزیتور' : ''));
    flash('success', $meta['label'] . ' ' . $number . ' ثبت شد.');
    redirect('/admin/accounting/' . $kind);
}

function accounting_refresh_status(App $app, int $invoiceId): void
{
    $invoice = $app->db->one('SELECT total, status, kind FROM ak_invoices WHERE id = ?', [$invoiceId]);
    if (!$invoice || $invoice['status'] === 'cancelled' || $invoice['kind'] === 'proforma') {
        return;
    }
    $paid = (int) ($app->db->one('SELECT COALESCE(SUM(amount),0) AS s FROM ak_payments WHERE invoice_id = ?', [$invoiceId])['s'] ?? 0);
    $status = $paid <= 0 ? 'issued' : ($paid >= (int) $invoice['total'] ? 'paid' : 'partial');
    $app->db->run('UPDATE ak_invoices SET status = ? WHERE id = ?', [$status, $invoiceId]);
}

/** @return list<array<string, mixed>> */
function accounting_documents(App $app, string $kind): array
{
    return $app->db->all(
        'SELECT i.*, p.name AS party_name,
            COALESCE((SELECT SUM(amount) FROM ak_payments WHERE invoice_id = i.id), 0) AS paid,
            COALESCE((SELECT SUM(qty * unit_cost) FROM ak_invoice_items WHERE invoice_id = i.id), 0) AS cost
         FROM ak_invoices i
         JOIN ak_parties p ON p.id = i.party_id
         WHERE i.kind = ?
         ORDER BY i.id DESC
         LIMIT 80',
        [$kind]
    );
}

function accounting_sum(App $app, string $sql, array $params = []): int
{
    return (int) ($app->db->one($sql, $params)['s'] ?? 0);
}

/** @return array<string, int> */
function accounting_figures(App $app): array
{
    accounting_sync_shop($app);
    $sales = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "sale" AND status != "cancelled"');
    $cogs = accounting_sum(
        $app,
        'SELECT COALESCE(SUM(it.qty * it.unit_cost),0) AS s
         FROM ak_invoice_items it JOIN ak_invoices i ON i.id = it.invoice_id
         WHERE i.kind = "sale" AND i.status != "cancelled"'
    );
    $shopSales = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "sale" AND source = "shop" AND status != "cancelled"');
    $purchases = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "purchase" AND status != "cancelled"');
    $expenses = accounting_sum($app, 'SELECT COALESCE(SUM(amount),0) AS s FROM ak_expenses');
    $payroll = accounting_sum($app, 'SELECT COALESCE(SUM(base_pay + benefit + employer_share),0) AS s FROM ak_payroll');
    $payrollNet = accounting_sum($app, 'SELECT COALESCE(SUM(base_pay + benefit - deduction),0) AS s FROM ak_payroll');
    $proforma = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "proforma" AND status != "cancelled"');
    $missingCost = accounting_sum(
        $app,
        'SELECT COUNT(*) AS s FROM ak_invoice_items it JOIN ak_invoices i ON i.id = it.invoice_id
         WHERE i.kind = "sale" AND i.status != "cancelled" AND it.unit_cost = 0'
    );
    $dealerCommission = accounting_optional_sum($app, 'ak_dealer_commissions', 'SELECT COALESCE(SUM(amount),0) AS s FROM ak_dealer_commissions WHERE status = "earned"');
    $dealerPending = accounting_optional_sum($app, 'ak_dealer_commissions', 'SELECT COALESCE(SUM(amount),0) AS s FROM ak_dealer_commissions WHERE status = "pending"');
    $dealerSales = accounting_optional_sum($app, 'ak_dealer_orders', 'SELECT COALESCE(SUM(total),0) AS s FROM ak_dealer_orders WHERE status NOT IN ("submitted","cancelled")');
    $dealerCommissionSales = accounting_optional_sum($app, 'ak_dealer_orders', 'SELECT COALESCE(SUM(o.total),0) AS s FROM ak_dealer_orders o JOIN ak_dealers d ON d.id = o.dealer_id WHERE d.sale_model = "commission" AND o.status NOT IN ("submitted","cancelled")');
    $dealerGrossGap = accounting_optional_sum($app, 'ak_dealer_commissions', 'SELECT COUNT(*) AS s FROM ak_dealer_commissions WHERE status IN ("pending","earned") AND note LIKE "%بهای تمام‌شده ثبت نشده%"');
    $visitorCommission = accounting_sum($app, 'SELECT COALESCE(SUM(amount),0) AS s FROM ak_visitor_shares');
    $warehouseValue = accounting_optional_sum($app, 'ak_warehouse_stock', 'SELECT COALESCE(SUM(value),0) AS s FROM ak_warehouse_stock');
    $warehouseQty = accounting_optional_sum($app, 'ak_warehouse_stock', 'SELECT COALESCE(SUM(qty),0) AS s FROM ak_warehouse_stock');
    $onlinePaid = accounting_optional_sum($app, 'ak_shop_orders', 'SELECT COALESCE(SUM(total),0) AS s FROM ak_shop_orders WHERE payment_status = "paid" AND status != "cancelled"');
    $shopCount = accounting_optional_sum($app, 'ak_shop_orders', 'SELECT COUNT(*) AS s FROM ak_shop_orders WHERE invoice_id > 0 AND status != "cancelled"');
    $gross = $sales - $cogs;
    $selling = $dealerCommission + $visitorCommission;
    return [
        'sales' => $sales,
        'cogs' => $cogs,
        'gross' => $gross,
        'purchases' => $purchases,
        'expenses' => $expenses,
        'payroll' => $payroll,
        'payroll_net' => $payrollNet,
        'dealer_commission' => $dealerCommission,
        'dealer_pending' => $dealerPending,
        'dealer_sales' => $dealerSales,
        'dealer_commission_sales' => $dealerCommissionSales,
        'dealer_gross_gap' => $dealerGrossGap,
        'visitor_commission' => $visitorCommission,
        'warehouse_value' => $warehouseValue,
        'warehouse_qty' => $warehouseQty,
        'shop_sales' => $shopSales,
        'shop_count' => $shopCount,
        'online_paid' => $onlinePaid,
        'proforma' => $proforma,
        'missing_cost' => $missingCost,
        'selling' => $selling,
        'operating' => $gross - $selling - $payroll - $expenses,
    ];
}

function accounting_rate(int $part, int $whole): string
{
    if ($whole === 0) {
        return '—';
    }
    $negative = ($part < 0) !== ($whole < 0);
    $bp = intdiv(abs($part) * 10000, abs($whole));
    $major = intdiv($bp, 100);
    $minor = $bp % 100;
    $text = fa_digits((string) $major) . '.' . fa_digits(str_pad((string) $minor, 2, '0', STR_PAD_LEFT)) . '٪';
    return $negative ? '−' . $text : $text;
}

function accounting_optional_sum(App $app, string $table, string $sql): int
{
    $known = ['ak_dealer_commissions' => true, 'ak_dealer_orders' => true, 'ak_shop_orders' => true, 'ak_warehouse_stock' => true];
    if (!isset($known[$table])) {
        return 0;
    }
    $found = $app->db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    );
    if (!$found) {
        return 0;
    }
    try {
        return accounting_sum($app, $sql);
    } catch (Throwable) {
        return 0;
    }
}

function accounting_prefix(App $app, string $kind): string
{
    $meta = accounting_kind($kind);
    $fallback = $meta['prefix'] ?? 'AK';
    $custom = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', setting($app, 'accounting', 'prefix_' . $kind, '')) ?? '');
    if ($custom === '' || strlen($custom) > 6) {
        return $fallback;
    }
    return $custom;
}

/** @return array<string, string> */
function accounting_sheet(App $app): array
{
    $tax = (int) setting($app, 'accounting', 'sheet_tax_percent', '0');
    if ($tax < 0 || $tax > 100) {
        $tax = 0;
    }
    $days = (int) setting($app, 'accounting', 'sheet_proforma_days', '7');
    if ($days < 0 || $days > 365) {
        $days = 7;
    }
    $title = setting($app, 'accounting', 'sheet_title', '');
    if ($title === '') {
        $title = setting($app, 'theme-settings', 'site_name', 'آسان‌کشت');
    }
    $phone = setting($app, 'accounting', 'sheet_phone', '');
    if ($phone === '') {
        $phone = setting($app, 'theme-settings', 'phone', '');
    }
    $address = setting($app, 'accounting', 'sheet_address', '');
    if ($address === '') {
        $address = setting($app, 'theme-settings', 'address', '');
    }
    return [
        'title' => $title,
        'subtitle' => setting($app, 'accounting', 'sheet_subtitle', setting($app, 'theme-settings', 'tagline', 'ماشین‌آلات صنعتی کشاورزی')),
        'phone' => $phone,
        'address' => $address,
        'email' => setting($app, 'accounting', 'sheet_email', setting($app, 'theme-settings', 'email', '')),
        'economic' => setting($app, 'accounting', 'sheet_economic', ''),
        'national' => setting($app, 'accounting', 'sheet_national', ''),
        'postal' => setting($app, 'accounting', 'sheet_postal', ''),
        'header' => setting($app, 'accounting', 'sheet_header', ''),
        'footer' => setting($app, 'accounting', 'sheet_footer', ''),
        'tax_on' => setting($app, 'accounting', 'sheet_tax', '0') === '1' ? '1' : '0',
        'tax_percent' => (string) $tax,
        'show_paid' => setting($app, 'accounting', 'sheet_show_paid', '1') === '0' ? '0' : '1',
        'show_status' => setting($app, 'accounting', 'sheet_show_status', '1') === '0' ? '0' : '1',
        'proforma_days' => (string) $days,
        'sign_seller' => setting($app, 'accounting', 'sheet_sign_seller', 'مهر و امضای فروشنده'),
        'sign_buyer' => setting($app, 'accounting', 'sheet_sign_buyer', 'مهر و امضای خریدار'),
        'prefix_proforma' => accounting_prefix($app, 'proforma'),
        'prefix_sale' => accounting_prefix($app, 'sale'),
        'prefix_purchase' => accounting_prefix($app, 'purchase'),
    ];
}

function accounting_save_sheet(App $app): void
{
    $text = [
        'sheet_title' => post_string('sheet_title', 120),
        'sheet_subtitle' => post_string('sheet_subtitle', 160),
        'sheet_phone' => post_string('sheet_phone', 40),
        'sheet_address' => post_string('sheet_address', 240),
        'sheet_email' => post_string('sheet_email', 120),
        'sheet_economic' => post_string('sheet_economic', 40),
        'sheet_national' => post_string('sheet_national', 40),
        'sheet_postal' => post_string('sheet_postal', 20),
        'sheet_header' => post_string('sheet_header', 300),
        'sheet_footer' => post_string('sheet_footer', 400),
        'sheet_sign_seller' => post_string('sheet_sign_seller', 80),
        'sheet_sign_buyer' => post_string('sheet_sign_buyer', 80),
    ];
    if ($text['sheet_title'] === '') {
        flash('error', 'نام فروشنده در سربرگ خالی نماند.');
        redirect('/admin/accounting/invoice-settings');
    }
    foreach ($text as $key => $value) {
        save_setting($app, 'accounting', $key, $value);
    }
    $tax = (int) post_string('sheet_tax_percent', 3);
    $days = (int) post_string('sheet_proforma_days', 3);
    save_setting($app, 'accounting', 'sheet_tax_percent', (string) max(0, min(100, $tax)));
    save_setting($app, 'accounting', 'sheet_proforma_days', (string) max(0, min(365, $days)));
    save_setting($app, 'accounting', 'sheet_tax', isset($_POST['sheet_tax']) ? '1' : '0');
    save_setting($app, 'accounting', 'sheet_show_paid', isset($_POST['sheet_show_paid']) ? '1' : '0');
    save_setting($app, 'accounting', 'sheet_show_status', isset($_POST['sheet_show_status']) ? '1' : '0');
    foreach (['proforma', 'sale', 'purchase'] as $kind) {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', post_string('prefix_' . $kind, 6)) ?? '');
        save_setting($app, 'accounting', 'prefix_' . $kind, $prefix);
    }
    flash('success', 'سربرگ و تنظیم فاکتور ذخیره شد.');
    redirect('/admin/accounting/invoice-settings');
}

/** @return array{invoice: array<string, mixed>, items: list<array<string, mixed>>, payments: list<array<string, mixed>>}|null */
function accounting_invoice_bundle(App $app, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $invoice = $app->db->one(
        'SELECT i.*, p.name AS party_name, p.phone AS party_phone
         FROM ak_invoices i JOIN ak_parties p ON p.id = i.party_id WHERE i.id = ?',
        [$id]
    );
    if (!$invoice) {
        return null;
    }
    return [
        'invoice' => $invoice,
        'items' => $app->db->all('SELECT * FROM ak_invoice_items WHERE invoice_id = ? ORDER BY id', [$id]),
        'payments' => $app->db->all('SELECT * FROM ak_payments WHERE invoice_id = ? ORDER BY paid_at, id', [$id]),
    ];
}

/** @return list<array<string, mixed>> */
function accounting_visitors(App $app): array
{
    return $app->db->all(
        'SELECT v.id, v.name, v.phone, v.code, v.percent, v.basis, v.active, v.created_at,
            COALESCE(SUM(s.amount),0) AS earned
         FROM ak_visitors v
         LEFT JOIN ak_visitor_shares s ON s.visitor_id = v.id
         GROUP BY v.id, v.name, v.phone, v.code, v.percent, v.basis, v.active, v.created_at
         ORDER BY v.active DESC, v.name'
    );
}

function accounting_visitor_count(App $app): int
{
    return accounting_sum($app, 'SELECT COUNT(*) AS s FROM ak_visitors WHERE active = 1');
}

function accounting_visitor_by_code(App $app, string $code): ?array
{
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    if ($code === '') {
        return null;
    }
    return $app->db->one('SELECT * FROM ak_visitors WHERE code = ? AND active = 1', [$code]);
}

function accounting_visitor_back(int $id): string
{
    return $id > 0 ? '/admin/accounting/visitors/' . $id : '/admin/accounting/visitors';
}

function accounting_visitor_hold(App $app, int $id): string
{
    $bits = [];
    if ($app->db->one('SELECT id FROM ak_visitor_shares WHERE visitor_id = ? LIMIT 1', [$id])) {
        $bits[] = 'سهم ثبت‌شده';
    }
    if ($app->db->one('SELECT id FROM ak_invoices WHERE visitor_id = ? LIMIT 1', [$id])) {
        $bits[] = 'فاکتور';
    }
    $shop = $app->db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_shop_orders"'
    );
    if ($shop && $app->db->one('SELECT id FROM ak_shop_orders WHERE visitor_id = ? LIMIT 1', [$id])) {
        $bits[] = 'سفارش فروشگاه';
    }
    return implode('، ', $bits);
}

function accounting_save_visitor(App $app, int $id): void
{
    $back = accounting_visitor_back($id);
    $name = post_string('name', 120);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', post_string('code', 16)) ?? '');
    $percent = (int) post_string('percent', 3);
    $basis = post_string('basis', 12) === 'gross' ? 'gross' : 'sale';
    if (mb_strlen($name) < 2 || strlen($code) < 3 || $percent < 0 || $percent > 100) {
        flash('error', 'نام، کد حداقل ۳ نویسه و درصد صفر تا ۱۰۰ لازم است.');
        redirect($back);
    }
    $phone = normalize_phone(post_string('phone', 20));
    $active = isset($_POST['active']) ? 1 : 0;
    $taken = $app->db->one('SELECT id FROM ak_visitors WHERE code = ? AND id != ?', [$code, max(0, $id)]);
    if ($taken) {
        flash('error', 'این کد ویزیتور قبلاً ثبت شده است.');
        redirect($back);
    }
    if ($id > 0) {
        $existing = $app->db->one('SELECT id FROM ak_visitors WHERE id = ?', [$id]);
        if (!$existing) {
            flash('error', 'این ویزیتور پیدا نشد.');
            redirect('/admin/accounting/visitors');
        }
        $app->db->run(
            'UPDATE ak_visitors SET name = ?, phone = ?, code = ?, percent = ?, basis = ?, active = ? WHERE id = ?',
            [$name, $phone, $code, $percent, $basis, $active, $id]
        );
        ak_audit($app, 'ویزیتور', $name, 'ویرایش ' . $code . ' — ' . (string) $percent . '٪ از ' . ($basis === 'gross' ? 'سود' : 'فروش'));
        flash('success', 'ویرایش ویزیتور ذخیره شد. سهم‌های قبلی عوض نشدند.');
        redirect('/admin/accounting/visitors/' . $id);
    }
    $app->db->run(
        'INSERT INTO ak_visitors (name, phone, code, percent, basis, active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$name, $phone, $code, $percent, $basis, $active]
    );
    ak_audit($app, 'ویزیتور', $name, $code . ' — ' . (string) $percent . '٪ از ' . ($basis === 'gross' ? 'سود' : 'فروش'));
    flash('success', 'ویزیتور ذخیره شد.');
    redirect('/admin/accounting/visitors');
}

function accounting_delete_visitor(App $app, int $id): void
{
    if (!isset($_POST['confirm'])) {
        flash('error', 'حذف ویزیتور را تأیید کنید.');
        redirect('/admin/accounting/visitors');
    }
    $existing = $id > 0 ? $app->db->one('SELECT id, name, code FROM ak_visitors WHERE id = ?', [$id]) : null;
    if (!$existing) {
        flash('error', 'این ویزیتور پیدا نشد.');
        redirect('/admin/accounting/visitors');
    }
    $hold = accounting_visitor_hold($app, $id);
    if ($hold !== '') {
        flash('error', 'این ویزیتور حذف نمی‌شود چون ' . $hold . ' دارد. از ویرایش می‌توانید او را غیرفعال کنید. سهم‌های ثبت‌شده در سود و زیان می‌مانند.');
        redirect('/admin/accounting/visitors/' . $id);
    }
    $app->db->run('DELETE FROM ak_visitors WHERE id = ?', [$id]);
    ak_audit($app, 'ویزیتور', (string) $existing['name'], 'حذف شد؛ کد ' . (string) $existing['code']);
    flash('success', 'ویزیتور حذف شد.');
    redirect('/admin/accounting/visitors');
}

/** @return list<array<string, mixed>> */
function accounting_audit_dealers(App $app): array
{
    $found = $app->db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_dealers"'
    );
    if (!$found) {
        return [];
    }
    try {
        $rows = $app->db->all(
            'SELECT d.id, d.name, d.code, d.status, d.sale_model, d.level,
                COALESCE((SELECT SUM(c.amount) FROM ak_dealer_commissions c WHERE c.dealer_id = d.id AND c.status = "earned"), 0) AS earned,
                COALESCE((SELECT SUM(c.amount) FROM ak_dealer_commissions c WHERE c.dealer_id = d.id AND c.status = "pending"), 0) AS pending
             FROM ak_dealers d
             ORDER BY d.id DESC
             LIMIT 40'
        );
        foreach ($rows as $index => $row) {
            $rows[$index]['percent'] = null;
            $rows[$index]['basis'] = '';
            $rows[$index]['percent_note'] = 'بدون پورسانت';
            if ((string) $row['sale_model'] !== 'commission' || !function_exists('dealer_general_rule')) {
                continue;
            }
            $rule = dealer_general_rule($app, $row);
            $rows[$index]['percent'] = (int) $rule['percent'];
            $rows[$index]['basis'] = (string) ($rule['basis'] ?? 'sale');
            $specific = (int) ($rule['dealer_id'] ?? 0) > 0 || (string) ($rule['level'] ?? '') !== '';
            $rows[$index]['percent_note'] = $specific ? '' : 'پیش‌فرض شرکت';
        }
        return $rows;
    } catch (Throwable) {
        return [];
    }
}

function accounting_record_visitor_share(App $app, int $visitorId, string $source, int $refId, int $sale, int $cost): void
{
    if (!in_array($source, ['invoice', 'shop'], true) || $visitorId < 1 || $refId < 1) {
        return;
    }
    $visitor = $app->db->one('SELECT * FROM ak_visitors WHERE id = ? AND active = 1', [$visitorId]);
    if (!$visitor) {
        return;
    }
    $exists = $app->db->one('SELECT id FROM ak_visitor_shares WHERE source = ? AND ref_id = ?', [$source, $refId]);
    if ($exists) {
        return;
    }
    $basis = (string) $visitor['basis'] === 'gross' ? 'gross' : 'sale';
    $base = $basis === 'gross' ? max(0, $sale - $cost) : max(0, $sale);
    $amount = intdiv($base * (int) $visitor['percent'], 100);
    $app->db->run(
        'INSERT INTO ak_visitor_shares (visitor_id, source, ref_id, sale_amount, cost_amount, percent, basis, amount, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [$visitorId, $source, $refId, $sale, $cost, (int) $visitor['percent'], $basis, $amount]
    );
}

function accounting_sync_shop(App $app): void
{
    $found = $app->db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_shop_orders"'
    );
    if (!$found) {
        return;
    }
    try {
        $rows = $app->db->all('SELECT id FROM ak_shop_orders WHERE status != "cancelled" AND invoice_id = 0 ORDER BY id LIMIT 80');
    } catch (Throwable) {
        return;
    }
    foreach ($rows as $row) {
        accounting_sync_shop_order($app, (int) $row['id']);
    }
}

function accounting_sync_shop_order(App $app, int $orderId): void
{
    $order = $app->db->one('SELECT * FROM ak_shop_orders WHERE id = ?', [$orderId]);
    if (!$order) {
        return;
    }
    if ((string) $order['status'] === 'cancelled') {
        accounting_void_shop_order($app, $order);
        return;
    }
    accounting_recognize_shop_order($app, $order);
}

/** @param array<string, mixed> $order */
function accounting_recognize_shop_order(App $app, array $order): void
{
    if ((int) ($order['invoice_id'] ?? 0) > 0 || (string) $order['status'] === 'cancelled') {
        return;
    }
    $method = (string) ($order['payment_method'] ?? '');
    $online = in_array($method, ['zarinpal', 'zibal', 'saman'], true);
    $accepted = in_array((string) $order['status'], ['confirmed', 'preparing', 'shipped', 'delivered'], true);
    $paid = (string) ($order['payment_status'] ?? '') === 'paid';
    if ($online && !$paid) {
        return;
    }
    if (!$online && !$accepted) {
        return;
    }
    $existing = $app->db->one('SELECT id FROM ak_invoices WHERE source = "shop" AND source_id = ?', [(int) $order['id']]);
    if ($existing) {
        $app->db->run('UPDATE ak_shop_orders SET invoice_id = ? WHERE id = ?', [(int) $existing['id'], (int) $order['id']]);
        return;
    }
    $items = $app->db->all('SELECT * FROM ak_shop_order_items WHERE order_id = ? ORDER BY id', [(int) $order['id']]);
    if ($items === []) {
        return;
    }
    $phone = (string) ($order['phone'] ?? '');
    $party = $phone !== '' ? $app->db->one('SELECT id FROM ak_parties WHERE phone = ? ORDER BY id DESC LIMIT 1', [$phone]) : null;
    if (!$party) {
        $app->db->run('INSERT INTO ak_parties (name, phone, created_at) VALUES (?, ?, NOW())', [(string) $order['name'], $phone]);
        $partyId = (int) $app->db->id();
    } else {
        $partyId = (int) $party['id'];
    }
    $goods = 0;
    $cost = 0;
    foreach ($items as $item) {
        $goods += (int) $item['line_total'];
        $cost += (int) ($item['unit_cost'] ?? 0) * (int) $item['qty'];
    }
    $shipping = (int) $order['shipping'];
    $tax = (int) $order['tax'];
    $total = $goods + $shipping + $tax;
    $number = accounting_prefix($app, 'sale') . '-' . date('ymd') . '-' . random_int(100, 999);
    $visitorId = (int) ($order['visitor_id'] ?? 0);
    $status = $paid ? 'paid' : 'issued';
    $invoiceId = (int) $app->db->transaction(function () use ($app, $order, $items, $partyId, $number, $total, $visitorId, $status, $shipping, $tax, $paid, $method): int {
        $app->db->run(
            'INSERT INTO ak_invoices (number, party_id, kind, status, notes, total, source, source_id, visitor_id, created_at)
             VALUES (?, ?, "sale", ?, ?, ?, "shop", ?, ?, NOW())',
            [$number, $partyId, $status, 'سفارش فروشگاه ' . $order['number'], $total, (int) $order['id'], $visitorId]
        );
        $invoiceId = (int) $app->db->id();
        foreach ($items as $item) {
            $qty = max(1, (int) $item['qty']);
            $app->db->run(
                'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?)',
                [$invoiceId, (string) $item['name'], $qty, (int) $item['unit_price'], (int) ($item['unit_cost'] ?? 0)]
            );
        }
        if ($shipping > 0) {
            $app->db->run(
                'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price, unit_cost) VALUES (?, "هزینه ارسال", 1, ?, ?)',
                [$invoiceId, $shipping, $shipping]
            );
        }
        if ($tax > 0) {
            $app->db->run(
                'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price, unit_cost) VALUES (?, "مالیات ارزش افزوده", 1, ?, ?)',
                [$invoiceId, $tax, $tax]
            );
        }
        if ($paid) {
            $app->db->run(
                'INSERT INTO ak_payments (invoice_id, amount, method, note, paid_at) VALUES (?, ?, ?, ?, CURDATE())',
                [$invoiceId, $total, $method, 'پرداخت سفارش ' . $order['number']]
            );
        }
        $app->db->run('UPDATE ak_shop_orders SET invoice_id = ? WHERE id = ?', [$invoiceId, (int) $order['id']]);
        return $invoiceId;
    });
    if ($visitorId > 0) {
        accounting_record_visitor_share($app, $visitorId, 'shop', (int) $order['id'], $goods, $cost);
    }
}

/** @param array<string, mixed> $order */
function accounting_void_shop_order(App $app, array $order): void
{
    $invoiceId = (int) ($order['invoice_id'] ?? 0);
    if ($invoiceId > 0) {
        $app->db->run('UPDATE ak_invoices SET status = "cancelled" WHERE id = ? AND source = "shop"', [$invoiceId]);
    }
    $app->db->run('DELETE FROM ak_visitor_shares WHERE source = "shop" AND ref_id = ?', [(int) $order['id']]);
}
