<?php

declare(strict_types=1);

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'accounting',
            'name' => 'حسابداری و گزارش',
            'description' => 'طرف‌حساب، فاکتور، دریافت، هزینه و گزارش',
        ];
    }

    public function install(Database $db): void
    {
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
                status VARCHAR(20) NOT NULL,
                notes VARCHAR(500) NOT NULL DEFAULT "",
                total BIGINT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                UNIQUE KEY number (number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_invoice_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                qty INT UNSIGNED NOT NULL,
                unit_price BIGINT NOT NULL,
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
    }

    public function boot(App $app): void
    {
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/accounting', 'label' => 'حسابداری', 'roles' => ['admin', 'employee']];
            $items[] = ['href' => '/admin/accounting/reports', 'label' => 'گزارش‌ها', 'roles' => ['admin', 'employee']];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $row = $app->db->one('SELECT COALESCE(SUM(total),0) AS s FROM ak_invoices WHERE status != "cancelled"');
            $cards[] = ['label' => 'جمع فاکتورها', 'value' => money((int) $row['s']), 'href' => '/admin/accounting'];
            return $cards;
        });
        $app->listen('account.cards', function (array $cards) use ($app): array {
            $user = $app->auth->user();
            if (!$user || empty($user['phone'])) {
                return $cards;
            }
            $row = $app->db->one(
                'SELECT COUNT(*) AS c FROM ak_invoices i JOIN ak_parties p ON p.id = i.party_id WHERE p.phone = ?',
                [$user['phone']]
            );
            $cards[] = ['label' => 'فاکتورهای من', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/account/invoices'];
            return $cards;
        });
        $app->router->get('/admin/accounting', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $app->view->plugin('accounting', 'index', [
                'title' => 'حسابداری',
                'invoices' => $app->db->all('SELECT i.*, p.name AS party_name FROM ak_invoices i JOIN ak_parties p ON p.id = i.party_id ORDER BY i.id DESC LIMIT 100'),
                'parties' => $app->db->all('SELECT * FROM ak_parties ORDER BY name'),
                'expenses' => $app->db->all('SELECT * FROM ak_expenses ORDER BY spent_at DESC, id DESC LIMIT 50'),
            ]);
        });
        $app->router->post('/admin/accounting/invoices', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $partyId = $this->partyId($app);
            $items = $this->itemsFromPost();
            if ($partyId === 0 || $items === []) {
                flash('error', 'طرف‌حساب و حداقل یک ردیف کالا لازم است.');
                redirect('/admin/accounting');
            }
            $total = 0;
            foreach ($items as $item) {
                $total += $item['qty'] * $item['unit_price'];
            }
            $number = 'AK-' . date('ymd') . '-' . random_int(100, 999);
            $app->db->transaction(function () use ($app, $number, $partyId, $total, $items): void {
                $app->db->run(
                    'INSERT INTO ak_invoices (number, party_id, status, notes, total, created_at) VALUES (?, ?, "issued", ?, ?, NOW())',
                    [$number, $partyId, post_string('notes', 500), $total]
                );
                $invoiceId = (int) $app->db->id();
                foreach ($items as $item) {
                    $app->db->run(
                        'INSERT INTO ak_invoice_items (invoice_id, title, qty, unit_price) VALUES (?, ?, ?, ?)',
                        [$invoiceId, $item['title'], $item['qty'], $item['unit_price']]
                    );
                }
            });
            flash('success', 'فاکتور ' . $number . ' ثبت شد.');
            redirect('/admin/accounting');
        });
        $app->router->post('/admin/accounting/payments', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $invoiceId = (int) post_string('invoice_id', 12);
            $amount = (int) post_string('amount', 16);
            $method = post_string('method', 40);
            $date = post_string('paid_at', 10);
            if ($invoiceId < 1 || $amount < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                flash('error', 'مبلغ و تاریخ پرداخت را کامل کنید.');
                redirect('/admin/accounting');
            }
            if (!in_array($method, ['cash', 'card', 'transfer'], true)) {
                $method = 'transfer';
            }
            $app->db->run(
                'INSERT INTO ak_payments (invoice_id, amount, method, note, paid_at) VALUES (?, ?, ?, ?, ?)',
                [$invoiceId, $amount, $method, post_string('note', 240), $date]
            );
            $this->refreshStatus($app, $invoiceId);
            flash('success', 'دریافت ثبت شد.');
            redirect('/admin/accounting');
        });
        $app->router->post('/admin/accounting/expenses', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $title = post_string('title', 180);
            $amount = (int) post_string('amount', 16);
            $date = post_string('spent_at', 10);
            if ($title === '' || $amount < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                flash('error', 'عنوان، مبلغ و تاریخ هزینه را کامل کنید.');
                redirect('/admin/accounting');
            }
            $app->db->run(
                'INSERT INTO ak_expenses (title, category, amount, spent_at) VALUES (?, ?, ?, ?)',
                [$title, post_string('category', 80) ?: 'عمومی', $amount, $date]
            );
            flash('success', 'هزینه ثبت شد.');
            redirect('/admin/accounting');
        });
        $app->router->post('/admin/accounting/from-quote', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $quote = $app->db->one('SELECT * FROM ak_quote_requests WHERE id = ?', [(int) post_string('quote_id', 12)]);
            if (!$quote) {
                redirect('/admin/quotes');
            }
            $party = $app->db->one('SELECT * FROM ak_parties WHERE phone = ?', [$quote['phone']]);
            if (!$party) {
                $app->db->run('INSERT INTO ak_parties (name, phone, created_at) VALUES (?, ?, NOW())', [$quote['name'], $quote['phone']]);
            }
            $app->db->run('UPDATE ak_quote_requests SET status = "reviewing" WHERE id = ?', [(int) $quote['id']]);
            flash('success', 'طرف‌حساب از روی درخواست ساخته شد. فاکتور را با مبلغ واقعی همین‌جا ثبت کنید.');
            redirect('/admin/accounting');
        });
        $app->router->get('/admin/accounting/reports', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $paid = (int) ($app->db->one('SELECT COALESCE(SUM(amount),0) AS s FROM ak_payments')['s'] ?? 0);
            $expense = (int) ($app->db->one('SELECT COALESCE(SUM(amount),0) AS s FROM ak_expenses')['s'] ?? 0);
            $open = $app->db->all(
                'SELECT i.number, i.total, p.name, COALESCE(SUM(pay.amount),0) AS paid
                 FROM ak_invoices i
                 JOIN ak_parties p ON p.id = i.party_id
                 LEFT JOIN ak_payments pay ON pay.invoice_id = i.id
                 WHERE i.status != "cancelled"
                 GROUP BY i.id, i.number, i.total, p.name
                 HAVING paid < i.total
                 ORDER BY i.id DESC'
            );
            $months = $app->db->all(
                'SELECT DATE_FORMAT(paid_at, "%Y-%m") AS ym, SUM(amount) AS total FROM ak_payments
                 WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY ym ORDER BY ym'
            );
            $app->view->plugin('accounting', 'reports', [
                'title' => 'گزارش‌ها',
                'paid' => $paid,
                'expense' => $expense,
                'open' => $open,
                'months' => $months,
            ]);
        });
        $app->router->get('/account/invoices', function () use ($app): void {
            $user = require_user($app);
            $rows = [];
            if (!empty($user['phone'])) {
                $rows = $app->db->all(
                    'SELECT i.* FROM ak_invoices i JOIN ak_parties p ON p.id = i.party_id WHERE p.phone = ? ORDER BY i.id DESC',
                    [$user['phone']]
                );
            }
            $app->view->plugin('accounting', 'my-invoices', [
                'title' => 'فاکتورهای من',
                'rows' => $rows,
            ], 'layouts/site');
        });
    }

    private function partyId(App $app): int
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

    private function itemsFromPost(): array
    {
        $titles = $_POST['item_title'] ?? [];
        $qtys = $_POST['item_qty'] ?? [];
        $prices = $_POST['item_price'] ?? [];
        if (!is_array($titles)) {
            return [];
        }
        $items = [];
        foreach ($titles as $i => $title) {
            $title = trim((string) $title);
            $qty = (int) ($qtys[$i] ?? 0);
            $price = (int) en_digits((string) ($prices[$i] ?? '0'));
            if ($title === '' || $qty < 1) {
                continue;
            }
            $items[] = ['title' => mb_substr($title, 0, 180), 'qty' => $qty, 'unit_price' => max(0, $price)];
        }
        return $items;
    }

    private function refreshStatus(App $app, int $invoiceId): void
    {
        $invoice = $app->db->one('SELECT total, status FROM ak_invoices WHERE id = ?', [$invoiceId]);
        if (!$invoice || $invoice['status'] === 'cancelled') {
            return;
        }
        $paid = (int) ($app->db->one('SELECT COALESCE(SUM(amount),0) AS s FROM ak_payments WHERE invoice_id = ?', [$invoiceId])['s'] ?? 0);
        $status = $paid <= 0 ? 'issued' : ($paid >= (int) $invoice['total'] ? 'paid' : 'partial');
        $app->db->run('UPDATE ak_invoices SET status = ? WHERE id = ?', [$status, $invoiceId]);
    }
};
