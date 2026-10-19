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
    accounting_add_column($db, 'ak_invoices', 'kind', 'VARCHAR(20) NOT NULL DEFAULT "sale"');
    accounting_add_column($db, 'ak_invoice_items', 'unit_cost', 'BIGINT NOT NULL DEFAULT 0');
    $ready = true;
}

function accounting_add_column(Database $db, string $table, string $column, string $definition): void
{
    $tables = ['ak_invoices' => true, 'ak_invoice_items' => true];
    $columns = ['kind' => true, 'unit_cost' => true];
    if (!isset($tables[$table], $columns[$column])) {
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
    $number = $meta['prefix'] . '-' . date('ymd') . '-' . random_int(100, 999);
    $status = $kind === 'proforma' ? 'draft' : 'issued';
    $app->db->transaction(function () use ($app, $number, $partyId, $kind, $status, $total, $items): void {
        $app->db->run(
            'INSERT INTO ak_invoices (number, party_id, kind, status, notes, total, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$number, $partyId, $kind, $status, post_string('notes', 500), $total]
        );
        $invoiceId = (int) $app->db->id();
        foreach ($items as $item) {
            $app->db->run(
                'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?)',
                [$invoiceId, $item['title'], $item['qty'], $item['unit_price'], $item['unit_cost']]
            );
        }
    });
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
    $sales = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "sale" AND status != "cancelled"');
    $cogs = accounting_sum(
        $app,
        'SELECT COALESCE(SUM(it.qty * it.unit_cost),0) AS s
         FROM ak_invoice_items it JOIN ak_invoices i ON i.id = it.invoice_id
         WHERE i.kind = "sale" AND i.status != "cancelled"'
    );
    $purchases = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "purchase" AND status != "cancelled"');
    $expenses = accounting_sum($app, 'SELECT COALESCE(SUM(amount),0) AS s FROM ak_expenses');
    $payroll = accounting_sum($app, 'SELECT COALESCE(SUM(base_pay + benefit - deduction),0) AS s FROM ak_payroll');
    $proforma = accounting_sum($app, 'SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE kind = "proforma" AND status NOT IN ("cancelled")');
    return [
        'sales' => $sales,
        'cogs' => $cogs,
        'gross' => $sales - $cogs,
        'purchases' => $purchases,
        'expenses' => $expenses,
        'payroll' => $payroll,
        'operating' => $sales - $cogs - $expenses - $payroll,
        'proforma' => $proforma,
    ];
}
