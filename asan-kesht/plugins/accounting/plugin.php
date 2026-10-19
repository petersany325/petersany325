<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'accounting',
            'name' => 'حسابداری و گزارش',
            'description' => 'پیش‌فاکتور، فروش، خرید، چک، حقوق، هزینه و سود',
        ];
    }

    public function install(Database $db): void
    {
        accounting_schema($db);
    }

    public function boot(App $app): void
    {
        accounting_schema($app->db);
        $roles = accounting_roles();
        $app->listen('admin.menu', function (array $items) use ($roles): array {
            $items[] = ['href' => '/admin/accounting', 'label' => 'خلاصه حسابداری', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/proforma', 'label' => 'پیش‌فاکتور', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/sale', 'label' => 'فاکتور فروش', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/purchase', 'label' => 'فاکتور خرید', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/reports', 'label' => 'سود و زیان', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/audit', 'label' => 'حسابرسی', 'roles' => ['admin', 'accountant']];
            $items[] = ['href' => '/admin/accounting/visitors', 'label' => 'ویزیتورها', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/checks', 'label' => 'چک و دسته‌چک', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/payroll', 'label' => 'حقوق و مزایا', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/expenses', 'label' => 'هزینه‌ها', 'roles' => $roles];
            $items[] = ['href' => '/admin/accounting/invoice-settings', 'label' => 'تنظیم فاکتور', 'roles' => $roles];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $figures = accounting_figures($app);
            $cards[] = ['label' => 'فروش', 'value' => money($figures['sales']), 'href' => '/admin/accounting/sale'];
            $cards[] = ['label' => 'سود ناخالص', 'value' => money($figures['gross']) . ' (' . accounting_rate($figures['gross'], $figures['sales']) . ')', 'href' => '/admin/accounting/reports'];
            return $cards;
        });
        $app->listen('account.cards', function (array $cards) use ($app): array {
            $user = $app->auth->user();
            if (!$user || empty($user['phone'])) {
                return $cards;
            }
            $row = $app->db->one(
                'SELECT COUNT(*) AS c FROM ak_invoices i JOIN ak_parties p ON p.id = i.party_id WHERE p.phone = ? AND i.kind IN ("sale","proforma")',
                [$user['phone']]
            );
            $cards[] = ['label' => 'فاکتورهای من', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/account/invoices'];
            return $cards;
        });
        $app->router->get('/admin/accounting', function () use ($app): void {
            require_role($app, accounting_roles());
            $app->view->plugin('accounting', 'index', [
                'title' => 'حسابداری',
                'figures' => accounting_figures($app),
            ]);
        });
        $app->router->get('/admin/accounting/reports', function () use ($app): void {
            require_role($app, accounting_roles());
            $open = $app->db->all(
                'SELECT i.number, i.total, i.kind, p.name, COALESCE(SUM(pay.amount),0) AS paid
                 FROM ak_invoices i
                 JOIN ak_parties p ON p.id = i.party_id
                 LEFT JOIN ak_payments pay ON pay.invoice_id = i.id
                 WHERE i.kind = "sale" AND i.status != "cancelled"
                 GROUP BY i.id, i.number, i.total, i.kind, p.name
                 HAVING paid < i.total
                 ORDER BY i.id DESC'
            );
            $checks = $app->db->all('SELECT * FROM ak_checks WHERE status = "pending" ORDER BY due_on, id LIMIT 30');
            $app->view->plugin('accounting', 'reports', [
                'title' => 'سود و زیان',
                'figures' => accounting_figures($app),
                'open' => $open,
                'checks' => $checks,
            ]);
        });
        $app->router->get('/admin/accounting/expenses', function () use ($app): void {
            require_role($app, accounting_roles());
            $app->view->plugin('accounting', 'expenses', [
                'title' => 'هزینه‌ها',
                'rows' => $app->db->all('SELECT * FROM ak_expenses ORDER BY spent_at DESC, id DESC LIMIT 80'),
            ]);
        });
        $app->router->get('/admin/accounting/checks', function () use ($app): void {
            require_role($app, accounting_roles());
            $app->view->plugin('accounting', 'checks', [
                'title' => 'چک و دسته‌چک',
                'books' => $app->db->all('SELECT * FROM ak_checkbooks ORDER BY id DESC'),
                'rows' => $app->db->all('SELECT * FROM ak_checks ORDER BY due_on DESC, id DESC LIMIT 80'),
            ]);
        });
        $app->router->get('/admin/accounting/audit', function () use ($app): void {
            require_role($app, ['admin', 'accountant']);
            ak_audit($app, '', '', '');
            try {
                $events = $app->db->all('SELECT * FROM ak_audit ORDER BY id DESC LIMIT 60');
            } catch (Throwable) {
                $events = [];
            }
            $app->view->plugin('accounting', 'audit', [
                'title' => 'حسابرسی',
                'figures' => accounting_figures($app),
                'visitors' => accounting_visitors($app),
                'dealers' => accounting_audit_dealers($app),
                'events' => $events,
            ]);
        });
        $app->router->get('/admin/accounting/visitors', function () use ($app): void {
            require_role($app, accounting_roles());
            $editId = (int) ($_GET['id'] ?? 0);
            if ($editId > 0) {
                redirect('/admin/accounting/visitors/' . $editId);
            }
            $app->view->plugin('accounting', 'visitors', [
                'title' => 'ویزیتورها',
                'rows' => accounting_visitors($app),
            ]);
        });
        $app->router->post('/admin/accounting/visitors', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            accounting_save_visitor($app, 0);
        });
        $app->router->post('/admin/accounting/visitors/{id}/delete', function (array $params) use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            accounting_delete_visitor($app, (int) ($params['id'] ?? 0));
        });
        $app->router->get('/admin/accounting/visitors/{id}', function (array $params) use ($app): void {
            require_role($app, accounting_roles());
            $id = (int) ($params['id'] ?? 0);
            $person = $id > 0 ? $app->db->one('SELECT * FROM ak_visitors WHERE id = ?', [$id]) : null;
            if (!$person) {
                flash('error', 'این ویزیتور پیدا نشد.');
                redirect('/admin/accounting/visitors');
            }
            $app->view->plugin('accounting', 'visitor-form', [
                'title' => 'ویرایش ویزیتور',
                'person' => $person,
            ]);
        });
        $app->router->post('/admin/accounting/visitors/{id}', function (array $params) use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            accounting_save_visitor($app, (int) ($params['id'] ?? 0));
        });
        $app->router->get('/admin/accounting/payroll', function () use ($app): void {
            require_role($app, accounting_roles());
            $app->view->plugin('accounting', 'payroll', [
                'title' => 'حقوق و مزایا',
                'rows' => $app->db->all('SELECT * FROM ak_payroll ORDER BY period DESC, id DESC LIMIT 80'),
            ]);
        });
        $app->router->get('/admin/accounting/invoice-settings', function () use ($app): void {
            require_role($app, accounting_roles());
            $app->view->plugin('accounting', 'settings', [
                'title' => 'تنظیم فاکتور و سربرگ',
                'sheet' => accounting_sheet($app),
            ]);
        });
        $app->router->post('/admin/accounting/invoice-settings', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            accounting_save_sheet($app);
        });
        $app->router->get('/admin/accounting/print/{id}', function (array $params) use ($app): void {
            require_role($app, accounting_roles());
            $bundle = accounting_invoice_bundle($app, (int) $params['id']);
            if ($bundle === null) {
                flash('error', 'این فاکتور پیدا نشد.');
                redirect('/admin/accounting');
            }
            $kind = (string) $bundle['invoice']['kind'];
            $internal = $kind === 'sale' && (string) ($_GET['internal'] ?? '') === '1';
            $app->view->plugin('accounting', 'print', [
                'title' => (string) $bundle['invoice']['number'],
                'sheet' => accounting_sheet($app),
                'invoice' => $bundle['invoice'],
                'items' => $bundle['items'],
                'payments' => $bundle['payments'],
                'internal' => $internal,
                'back' => '/admin/accounting/' . $kind,
            ], 'layouts/print');
        });
        $warehouseLib = dirname(__DIR__) . '/warehouses/lib.php';
        if (is_file($warehouseLib)) {
            require_once $warehouseLib;
            warehouse_routes($app);
        }
        $app->router->get('/admin/accounting/{kind}', function (array $params) use ($app): void {
            require_role($app, accounting_roles());
            $kind = (string) $params['kind'];
            $meta = accounting_kind($kind);
            if ($meta === null) {
                redirect('/admin/accounting');
            }
            $app->view->plugin('accounting', 'documents', [
                'title' => $meta['label'],
                'kind' => $kind,
                'meta' => $meta,
                'rows' => accounting_documents($app, $kind),
                'parties' => $app->db->all('SELECT * FROM ak_parties ORDER BY name'),
                'visitors' => $kind === 'sale' ? $app->db->all('SELECT id, name, percent, basis FROM ak_visitors WHERE active = 1 ORDER BY name') : [],
            ]);
        });
        $app->router->post('/admin/accounting/documents', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            accounting_save_document($app, post_string('kind', 20));
        });
        $app->router->post('/admin/accounting/payments', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $invoiceId = (int) post_string('invoice_id', 12);
            $amount = accounting_amount(post_string('amount', 16));
            $method = post_string('method', 40);
            $date = accounting_date(post_string('paid_at', 10));
            $back = $this->back();
            if ($invoiceId < 1 || $amount < 1 || $date === null) {
                flash('error', 'مبلغ و تاریخ پرداخت را کامل کنید.');
                redirect($back);
            }
            if (!in_array($method, ['cash', 'card', 'transfer'], true)) {
                $method = 'transfer';
            }
            $app->db->run(
                'INSERT INTO ak_payments (invoice_id, amount, method, note, paid_at) VALUES (?, ?, ?, ?, ?)',
                [$invoiceId, $amount, $method, post_string('note', 240), $date]
            );
            accounting_refresh_status($app, $invoiceId);
            flash('success', 'دریافت ثبت شد.');
            redirect($back);
        });
        $app->router->post('/admin/accounting/expenses', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $title = post_string('title', 180);
            $amount = accounting_amount(post_string('amount', 16));
            $date = accounting_date(post_string('spent_at', 10));
            if ($title === '' || $amount < 1 || $date === null) {
                flash('error', 'عنوان، مبلغ و تاریخ هزینه را کامل کنید.');
                redirect('/admin/accounting/expenses');
            }
            $app->db->run(
                'INSERT INTO ak_expenses (title, category, amount, spent_at) VALUES (?, ?, ?, ?)',
                [$title, post_string('category', 80) ?: 'عمومی', $amount, $date]
            );
            flash('success', 'هزینه ثبت شد.');
            redirect('/admin/accounting/expenses');
        });
        $app->router->post('/admin/accounting/checkbooks', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $title = post_string('title', 120);
            $start = (int) post_string('start_no', 12);
            $end = (int) post_string('end_no', 12);
            if ($title === '' || $start < 1 || $end < $start) {
                flash('error', 'عنوان دسته و بازه شماره چک را درست وارد کنید.');
                redirect('/admin/accounting/checks');
            }
            $app->db->run(
                'INSERT INTO ak_checkbooks (title, bank, start_no, end_no, created_at) VALUES (?, ?, ?, ?, NOW())',
                [$title, post_string('bank', 80), $start, $end]
            );
            flash('success', 'دسته‌چک ثبت شد.');
            redirect('/admin/accounting/checks');
        });
        $app->router->post('/admin/accounting/checks', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $direction = post_string('direction', 8) === 'in' ? 'in' : 'out';
            $number = post_string('number', 40);
            $amount = accounting_amount(post_string('amount', 16));
            $due = accounting_date(post_string('due_on', 10));
            if ($number === '' || $amount < 1 || $due === null) {
                flash('error', 'شماره، مبلغ و سررسید چک لازم است.');
                redirect('/admin/accounting/checks');
            }
            $book = (int) post_string('checkbook_id', 12);
            $app->db->run(
                'INSERT INTO ak_checks (checkbook_id, direction, number, bank, amount, due_on, party_name, status, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "pending", ?, NOW())',
                [$book > 0 ? $book : null, $direction, $number, post_string('bank', 80), $amount, $due, post_string('party_name', 160), post_string('note', 240)]
            );
            flash('success', 'چک ثبت شد.');
            redirect('/admin/accounting/checks');
        });
        $app->router->post('/admin/accounting/checks/status', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $status = post_string('status', 20);
            if (!in_array($status, ['pending', 'cleared', 'bounced', 'void'], true)) {
                $status = 'pending';
            }
            $app->db->run('UPDATE ak_checks SET status = ? WHERE id = ?', [$status, (int) post_string('id', 12)]);
            flash('success', 'وضعیت چک ذخیره شد.');
            redirect('/admin/accounting/checks');
        });
        $app->router->post('/admin/accounting/payroll', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $person = post_string('person', 120);
            $period = en_digits(post_string('period', 7));
            $base = accounting_amount(post_string('base_pay', 16));
            if ($person === '' || !preg_match('/^\d{4}-\d{2}$/', $period) || $base < 1) {
                flash('error', 'نام، دوره مانند 2026-01 و حقوق پایه لازم است.');
                redirect('/admin/accounting/payroll');
            }
            $paid = accounting_date(post_string('paid_on', 10));
            $app->db->run(
                'INSERT INTO ak_payroll (person, job_title, period, base_pay, benefit, deduction, employer_share, paid_on, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $person,
                    post_string('job_title', 80),
                    $period,
                    $base,
                    accounting_amount(post_string('benefit', 16)),
                    accounting_amount(post_string('deduction', 16)),
                    accounting_amount(post_string('employer_share', 16)),
                    $paid,
                    post_string('note', 240),
                ]
            );
            ak_audit($app, 'حقوق', $person, $period);
            flash('success', 'ردیف حقوق ثبت شد.');
            redirect('/admin/accounting/payroll');
        });
        $app->router->post('/admin/accounting/from-quote', function () use ($app): void {
            csrf_check();
            require_role($app, accounting_roles());
            $quote = $app->db->one('SELECT * FROM ak_quote_requests WHERE id = ?', [(int) post_string('quote_id', 12)]);
            if (!$quote) {
                redirect('/admin/quotes');
            }
            $party = $app->db->one('SELECT * FROM ak_parties WHERE phone = ?', [$quote['phone']]);
            if (!$party) {
                $app->db->run('INSERT INTO ak_parties (name, phone, created_at) VALUES (?, ?, NOW())', [$quote['name'], $quote['phone']]);
            }
            $app->db->run('UPDATE ak_quote_requests SET status = "reviewing" WHERE id = ?', [(int) $quote['id']]);
            flash('success', 'طرف‌حساب از روی درخواست ساخته شد. پیش‌فاکتور را با مبلغ واقعی ثبت کنید.');
            redirect('/admin/accounting/proforma');
        });
        $app->router->get('/account/invoices', function () use ($app): void {
            $user = require_user($app);
            $rows = [];
            if (!empty($user['phone'])) {
                $rows = $app->db->all(
                    'SELECT i.* FROM ak_invoices i JOIN ak_parties p ON p.id = i.party_id WHERE p.phone = ? AND i.kind IN ("sale","proforma") ORDER BY i.id DESC',
                    [$user['phone']]
                );
            }
            $app->view->plugin('accounting', 'my-invoices', [
                'title' => 'فاکتورهای من',
                'rows' => $rows,
            ], 'layouts/site');
        });
        $app->router->get('/account/invoices/{id}/print', function (array $params) use ($app): void {
            $user = require_user($app);
            $bundle = accounting_invoice_bundle($app, (int) $params['id']);
            $phone = (string) ($user['phone'] ?? '');
            $kind = $bundle === null ? '' : (string) $bundle['invoice']['kind'];
            if ($bundle === null || $phone === '' || (string) $bundle['invoice']['party_phone'] !== $phone || !in_array($kind, ['sale', 'proforma'], true)) {
                flash('error', 'این فاکتور برای حساب شما نیست.');
                redirect('/account/invoices');
            }
            $app->view->plugin('accounting', 'print', [
                'title' => (string) $bundle['invoice']['number'],
                'sheet' => accounting_sheet($app),
                'invoice' => $bundle['invoice'],
                'items' => $bundle['items'],
                'payments' => $bundle['payments'],
                'internal' => false,
                'back' => '/account/invoices',
            ], 'layouts/print');
        });
    }

    private function back(): string
    {
        $back = post_string('return_to', 80);
        return str_starts_with($back, '/admin/accounting') ? $back : '/admin/accounting';
    }
};
