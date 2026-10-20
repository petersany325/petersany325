<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'representatives',
            'name' => 'نمایندگان و معاملات',
            'description' => 'پرونده نمایندگی، قیمت، سفارش، حساب، چک، پورسانت، بخشنامه و درخواست',
        ];
    }

    public function install(Database $db): void
    {
        dealer_install($db);
    }

    public function boot(App $app): void
    {
        if ($app->pluginEnabled('cooperation') && function_exists('cooperation_extend')) {
            cooperation_extend($app->db);
        }
        if ($app->pluginEnabled('attachments') && function_exists('attachment_column')) {
            attachment_column($app->db, 'ak_circulars');
            attachment_column($app->db, 'ak_dealer_requests');
        }
        $app->listen('site.nav', function (array $items): array {
            $items[] = ['href' => '/estelam', 'label' => 'استعلام نمایندگی'];
            return $items;
        });
        $app->listen('admin.menu', function (array $items): array {
            $company = ['admin', 'employee', 'accountant', 'region_manager'];
            $editors = ['admin', 'employee'];
            $finance = ['admin', 'accountant'];
            $items[] = ['href' => '/admin/dealers', 'label' => 'نمایندگان', 'roles' => $company];
            $items[] = ['href' => '/admin/dealers/orders', 'label' => 'سفارش نمایندگان', 'roles' => $company];
            $items[] = ['href' => '/admin/dealers/prices', 'label' => 'قیمت نمایندگی', 'roles' => $editors];
            $items[] = ['href' => '/admin/dealers/money', 'label' => 'حساب و چک', 'roles' => $finance];
            $items[] = ['href' => '/admin/dealers/commissions', 'label' => 'پورسانت', 'roles' => $finance];
            $items[] = ['href' => '/admin/dealers/circulars', 'label' => 'بخشنامه‌ها', 'roles' => $editors];
            $items[] = ['href' => '/admin/dealers/requests', 'label' => 'درخواست نمایندگان', 'roles' => $company];
            $items[] = ['href' => '/admin/dealers/settings', 'label' => 'تنظیمات نمایندگی', 'roles' => ['admin']];
            $items[] = ['href' => '/rep', 'label' => 'پنل نمایندگی', 'roles' => ['representative']];
            $items[] = ['href' => '/rep/prices', 'label' => 'قیمت من', 'roles' => ['representative']];
            $items[] = ['href' => '/rep/orders', 'label' => 'سفارش من', 'roles' => ['representative']];
            $items[] = ['href' => '/rep/account', 'label' => 'حساب و چک من', 'roles' => ['representative']];
            $items[] = ['href' => '/rep/circulars', 'label' => 'بخشنامه‌های من', 'roles' => ['representative']];
            $items[] = ['href' => '/rep/requests', 'label' => 'درخواست‌های من', 'roles' => ['representative']];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = ['href' => '/admin/dealers/settings', 'title' => 'سیاست نمایندگی', 'text' => 'انحصار شهر، مدل فروش، پورسانت، مهلت اعتبار و قاعده‌های خاص'];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $user = $app->auth->user();
            if (!$user) {
                return $cards;
            }
            $scope = dealer_scope($user);
            $orders = $app->db->one(
                'SELECT COUNT(*) AS c FROM ak_dealer_orders o JOIN ak_dealers d ON d.id = o.dealer_id WHERE o.status = "submitted"' . $scope['sql'],
                $scope['params']
            );
            $cards[] = [
                'label' => 'سفارش نماینده',
                'value' => fa_digits((string) ($orders['c'] ?? 0)),
                'href' => '/admin/dealers/orders',
                'roles' => ['admin', 'employee', 'accountant', 'region_manager'],
            ];
            if (in_array($user['role'], ['admin', 'accountant'], true)) {
                $checks = $app->db->one('SELECT COUNT(*) AS c FROM ak_dealer_checks WHERE status = "pending" AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)');
                $cards[] = [
                    'label' => 'چک نزدیک سررسید',
                    'value' => fa_digits((string) ($checks['c'] ?? 0)),
                    'href' => '/admin/dealers/money',
                    'roles' => ['admin', 'accountant'],
                ];
            }
            return $cards;
        });

        $app->router->get('/estelam', function () use ($app): void {
            $code = strtoupper(trim((string) ($_GET['code'] ?? '')));
            $dealer = null;
            if ($code !== '') {
                $dealer = $app->db->one('SELECT * FROM ak_dealers WHERE code = ?', [$code]);
            }
            $app->view->plugin('representatives', 'verify', [
                'title' => 'استعلام نمایندگی',
                'code' => $code,
                'dealer' => $dealer,
            ], 'layouts/site');
        });

        $app->router->get('/admin/dealers/settings', function () use ($app): void {
            require_role($app, ['admin']);
            $app->view->plugin('representatives', 'settings', [
                'title' => 'تنظیمات نمایندگی',
                'values' => $this->settings($app),
                'rules' => $app->db->all('SELECT * FROM ak_commission_rules ORDER BY id DESC'),
                'lines' => dealer_lines(),
            ]);
        });
        $app->router->post('/admin/dealers/settings', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            save_setting($app, 'representatives', 'enforce_exclusive', isset($_POST['enforce_exclusive']) ? '1' : '0');
            save_setting($app, 'representatives', 'allow_resale', isset($_POST['allow_resale']) ? '1' : '0');
            save_setting($app, 'representatives', 'allow_commission', isset($_POST['allow_commission']) ? '1' : '0');
            $days = max(0, min(365, (int) post_string('default_credit_days', 4)));
            $percent = max(0, min(100, (int) post_string('default_commission_percent', 3)));
            $returnDays = max(0, min(90, (int) post_string('return_days', 3)));
            $basis = post_string('commission_basis', 20) === 'gross' ? 'gross' : 'sale';
            $settle = post_string('commission_settle', 20) === 'sale' ? 'sale' : 'collection';
            save_setting($app, 'representatives', 'default_credit_days', (string) $days);
            save_setting($app, 'representatives', 'default_commission_percent', (string) $percent);
            save_setting($app, 'representatives', 'return_days', (string) $returnDays);
            save_setting($app, 'representatives', 'commission_basis', $basis);
            save_setting($app, 'representatives', 'commission_settle', $settle);
            flash('success', 'تنظیمات نمایندگی ذخیره شد.');
            redirect('/admin/dealers/settings');
        });
        $app->router->post('/admin/dealers/rules', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            $percent = max(0, min(100, (int) post_string('percent', 3)));
            $line = array_key_exists(post_string('line', 20), dealer_lines()) ? post_string('line', 20) : '';
            $basis = post_string('basis', 20) === 'gross' ? 'gross' : 'sale';
            $settle = post_string('settle_on', 20) === 'sale' ? 'sale' : 'collection';
            $dealerId = (int) post_string('dealer_id', 12);
            if ($dealerId > 0 && !$app->db->one('SELECT id FROM ak_dealers WHERE id = ?', [$dealerId])) {
                $dealerId = 0;
            }
            $app->db->run(
                'INSERT INTO ak_commission_rules (dealer_id, level, line, percent, basis, settle_on) VALUES (?, ?, ?, ?, ?, ?)',
                [$dealerId, post_string('level', 40), $line, $percent, $basis, $settle]
            );
            ak_audit($app, 'پورسانت نماینده', 'قاعده ' . (string) $percent . '٪', 'نماینده ' . (string) $dealerId);
            flash('success', 'قاعده پورسانت ثبت شد.');
            redirect('/admin/dealers/settings');
        });

        $app->router->get('/admin/dealers/prices', function () use ($app): void {
            dealer_require_editor($app);
            $app->view->plugin('representatives', 'prices', [
                'title' => 'قیمت نمایندگی',
                'machines' => $app->db->all('SELECT * FROM ak_machines WHERE active = 1 ORDER BY sort_order, id'),
                'prices' => $app->db->all('SELECT p.*, m.name AS machine_name, d.name AS dealer_name FROM ak_dealer_prices p JOIN ak_machines m ON m.id = p.machine_id LEFT JOIN ak_dealers d ON d.id = p.dealer_id ORDER BY p.id DESC LIMIT 200'),
                'dealers' => $app->db->all('SELECT id, name, code FROM ak_dealers ORDER BY name'),
            ]);
        });
        $app->router->post('/admin/dealers/prices', function () use ($app): void {
            csrf_check();
            dealer_require_editor($app);
            $machineId = (int) post_string('machine_id', 12);
            $dealerId = (int) post_string('dealer_id', 12);
            $price = dealer_money_input('price');
            $cost = dealer_money_input('cost');
            $level = $dealerId > 0 ? '' : post_string('level', 40);
            if ($machineId < 1 || $price < 1) {
                flash('error', 'مدل و قیمت را وارد کنید.');
                redirect('/admin/dealers/prices');
            }
            $app->db->run(
                'INSERT INTO ak_dealer_prices (dealer_id, level, machine_id, price, cost) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE price = VALUES(price), cost = VALUES(cost)',
                [$dealerId, $level, $machineId, $price, $cost]
            );
            flash('success', 'قیمت ذخیره شد.');
            redirect('/admin/dealers/prices');
        });

        $app->router->get('/admin/dealers/orders', function () use ($app): void {
            $user = dealer_require_company($app);
            $scope = dealer_scope($user);
            $rows = $app->db->all(
                'SELECT o.*, d.name, d.city FROM ak_dealer_orders o JOIN ak_dealers d ON d.id = o.dealer_id WHERE 1=1' . $scope['sql'] . ' ORDER BY o.id DESC LIMIT 200',
                $scope['params']
            );
            $app->view->plugin('representatives', 'orders', ['title' => 'سفارش نمایندگان', 'rows' => $rows]);
        });
        $app->router->get('/admin/dealers/orders/{id}', function (array $params) use ($app): void {
            $user = dealer_require_company($app);
            $scope = dealer_scope($user);
            $order = $app->db->one(
                'SELECT o.*, d.name, d.city, d.sale_model FROM ak_dealer_orders o JOIN ak_dealers d ON d.id = o.dealer_id WHERE o.id = ?' . $scope['sql'],
                array_merge([(int) $params['id']], $scope['params'])
            );
            if (!$order) {
                redirect('/admin/dealers/orders');
            }
            $app->view->plugin('representatives', 'order', [
                'title' => 'سفارش ' . $order['number'],
                'order' => $order,
                'items' => $app->db->all('SELECT * FROM ak_dealer_order_items WHERE order_id = ?', [(int) $order['id']]),
                'can_decide' => in_array($user['role'], ['admin', 'employee'], true),
            ]);
        });
        $app->router->post('/admin/dealers/orders/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = dealer_require_editor($app);
            $order = $app->db->one('SELECT * FROM ak_dealer_orders WHERE id = ?', [(int) $params['id']]);
            if (!$order) {
                redirect('/admin/dealers/orders');
            }
            $action = post_string('action', 20);
            if ($action === 'approve') {
                dealer_approve($app, $order, (int) $user['id']);
            }
            if ($action === 'cancel') {
                dealer_cancel($app, $order, (int) $user['id']);
            }
            $next = post_string('status', 20);
            if (in_array($next, ['preparing', 'shipped', 'delivered'], true) && $order['status'] !== 'cancelled' && $order['status'] !== 'submitted') {
                $app->db->run('UPDATE ak_dealer_orders SET status = ? WHERE id = ?', [$next, (int) $order['id']]);
                flash('success', 'مرحله سفارش به‌روز شد.');
            }
            redirect('/admin/dealers/orders/' . $order['id']);
        });

        $app->router->get('/admin/dealers/money', function () use ($app): void {
            dealer_require_finance($app);
            $app->view->plugin('representatives', 'money', [
                'title' => 'حساب، چک و پرداخت',
                'payments' => $app->db->all('SELECT p.*, d.name FROM ak_dealer_payments p JOIN ak_dealers d ON d.id = p.dealer_id ORDER BY p.id DESC LIMIT 100'),
                'checks' => $app->db->all('SELECT c.*, d.name FROM ak_dealer_checks c JOIN ak_dealers d ON d.id = c.dealer_id ORDER BY c.due_date, c.id DESC LIMIT 100'),
                'disputes' => $app->db->all('SELECT x.*, d.name FROM ak_dealer_disputes x JOIN ak_dealers d ON d.id = x.dealer_id ORDER BY x.id DESC LIMIT 50'),
                'dealers' => $app->db->all('SELECT id, name, code FROM ak_dealers ORDER BY name'),
            ]);
        });
        $app->router->post('/admin/dealers/payments/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = dealer_require_finance($app);
            $this->decidePayment($app, (int) $params['id'], (int) $user['id']);
        });
        $app->router->post('/admin/dealers/checks/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = dealer_require_finance($app);
            $this->decideCheck($app, (int) $params['id'], (int) $user['id']);
        });
        $app->router->post('/admin/dealers/adjust', function () use ($app): void {
            csrf_check();
            $user = dealer_require_finance($app);
            $dealerId = (int) post_string('dealer_id', 12);
            $amount = dealer_money_input('amount');
            $direction = post_string('direction', 10) === 'credit' ? 'credit' : 'debit';
            $note = post_string('note', 240);
            if ($dealerId < 1 || $amount < 1 || $note === '') {
                flash('error', 'نماینده، مبلغ و شرح سند اصلاحی لازم است.');
                redirect('/admin/dealers/money');
            }
            dealer_post($app, $dealerId, $direction, $amount, 'adjustment', 'adjustment', 0, $note, (int) $user['id']);
            flash('success', 'سند اصلاحی ثبت شد. ردیف قبلی دست نخورده است.');
            redirect('/admin/dealers/money');
        });
        $app->router->post('/admin/dealers/disputes/{id}', function (array $params) use ($app): void {
            csrf_check();
            dealer_require_finance($app);
            $reply = post_string('reply', 2000);
            if ($reply === '') {
                flash('error', 'پاسخ مغایرت را بنویسید.');
                redirect('/admin/dealers/money');
            }
            $app->db->run('UPDATE ak_dealer_disputes SET reply = ?, status = "answered" WHERE id = ?', [$reply, (int) $params['id']]);
            flash('success', 'پاسخ مغایرت ثبت شد.');
            redirect('/admin/dealers/money');
        });

        $app->router->get('/admin/dealers/commissions', function () use ($app): void {
            dealer_require_finance($app);
            $app->view->plugin('representatives', 'commissions', [
                'title' => 'پورسانت نمایندگان',
                'rows' => $app->db->all('SELECT c.*, d.name, o.number FROM ak_dealer_commissions c JOIN ak_dealers d ON d.id = c.dealer_id LEFT JOIN ak_dealer_orders o ON o.id = c.order_id AND c.order_id > 0 ORDER BY c.id DESC LIMIT 200'),
            ]);
        });

        $app->router->get('/admin/dealers/circulars', function () use ($app): void {
            dealer_require_editor($app);
            $app->view->plugin('representatives', 'circulars', [
                'title' => 'بخشنامه‌ها و قوانین',
                'rows' => $app->db->all('SELECT c.*, (SELECT COUNT(*) FROM ak_circular_receipts r WHERE r.circular_id = c.id AND r.status IN ("acked","doing","done","approved")) AS accepted FROM ak_circulars c ORDER BY c.id DESC'),
                'done' => $app->db->all('SELECT r.*, c.title, d.name FROM ak_circular_receipts r JOIN ak_circulars c ON c.id = r.circular_id JOIN ak_dealers d ON d.id = r.dealer_id WHERE r.status = "done" ORDER BY r.updated_at DESC'),
                'dealers' => $app->db->all('SELECT id, name, city, province FROM ak_dealers WHERE status = "active" ORDER BY name'),
            ]);
        });
        $app->router->post('/admin/dealers/circulars', function () use ($app): void {
            csrf_check();
            $user = dealer_require_editor($app);
            $this->publishCircular($app, (int) $user['id']);
        });
        $app->router->post('/admin/dealers/circulars/{id}', function (array $params) use ($app): void {
            csrf_check();
            dealer_require_editor($app);
            $app->db->run(
                'UPDATE ak_circular_receipts SET status = "approved", updated_at = NOW() WHERE circular_id = ? AND dealer_id = ? AND status = "done"',
                [(int) $params['id'], (int) post_string('dealer_id', 12)]
            );
            flash('success', 'انجام بخشنامه تأیید شد.');
            redirect('/admin/dealers/circulars');
        });

        $app->router->get('/admin/dealers/requests', function () use ($app): void {
            $user = dealer_require_company($app);
            $scope = dealer_scope($user);
            $rows = $app->db->all(
                'SELECT r.*, d.name FROM ak_dealer_requests r JOIN ak_dealers d ON d.id = r.dealer_id WHERE 1=1' . $scope['sql'] . ' ORDER BY r.id DESC LIMIT 200',
                $scope['params']
            );
            $app->view->plugin('representatives', 'requests', [
                'title' => 'درخواست‌های نمایندگان',
                'rows' => $rows,
                'role' => (string) $user['role'],
            ]);
        });
        $app->router->post('/admin/dealers/requests/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = dealer_require_company($app);
            $this->stepRequest($app, (int) $params['id'], $user);
        });

        $app->router->get('/admin/dealers/new', function () use ($app): void {
            dealer_require_editor($app);
            $this->dealerForm($app, null);
        });
        $app->router->get('/admin/dealers', function () use ($app): void {
            $user = dealer_require_company($app);
            $scope = dealer_scope($user);
            $rows = $app->db->all(
                'SELECT d.*, g.name AS region_name,
                    (SELECT r.percent FROM ak_commission_rules r WHERE r.dealer_id = d.id AND r.line = "" AND r.level = "" ORDER BY r.id DESC LIMIT 1) AS commission_percent
                 FROM ak_dealers d LEFT JOIN ak_dealer_regions g ON g.id = d.region_id WHERE 1=1' . $scope['sql'] . ' ORDER BY d.id DESC',
                $scope['params']
            );
            $app->view->plugin('representatives', 'index', [
                'title' => 'نمایندگان',
                'rows' => $rows,
                'regions' => $app->db->all('SELECT * FROM ak_dealer_regions ORDER BY name'),
                'managers' => $app->db->all('SELECT id, name FROM ak_users WHERE role = "region_manager" AND active = 1 ORDER BY name'),
                'can_edit' => in_array($user['role'], ['admin', 'employee'], true),
            ]);
        });
        $app->router->post('/admin/dealers/regions', function () use ($app): void {
            csrf_check();
            dealer_require_editor($app);
            $name = post_string('name', 120);
            $manager = (int) post_string('manager_user_id', 12);
            if ($name === '') {
                flash('error', 'نام منطقه را بنویسید.');
                redirect('/admin/dealers');
            }
            $app->db->run(
                'INSERT INTO ak_dealer_regions (name, manager_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE manager_user_id = VALUES(manager_user_id)',
                [$name, $manager > 0 ? $manager : null]
            );
            flash('success', 'منطقه ذخیره شد.');
            redirect('/admin/dealers');
        });
        $app->router->get('/admin/dealers/{id}', function (array $params) use ($app): void {
            $user = dealer_require_company($app);
            $dealer = dealer_find($app, (int) $params['id'], $user);
            if (!$dealer) {
                redirect('/admin/dealers');
            }
            $this->dealerForm($app, $dealer);
        });
        $app->router->post('/admin/dealers', function () use ($app): void {
            csrf_check();
            dealer_require_editor($app);
            $this->saveDealer($app);
        });
        $app->router->get('/admin/dealers/files/{id}', function (array $params) use ($app): void {
            $user = dealer_require_company($app);
            $file = $app->db->one('SELECT f.*, d.region_id FROM ak_dealer_files f JOIN ak_dealers d ON d.id = f.dealer_id WHERE f.id = ?', [(int) $params['id']]);
            if (!$file || !dealer_find($app, (int) $file['dealer_id'], $user)) {
                http_response_code(404);
                exit;
            }
            dealer_send_file($app, (string) $file['filename']);
        });

        $app->router->get('/rep', function () use ($app): void {
            $dealer = dealer_require_rep($app);
            $figures = ['earned' => null, 'potential' => null];
            if ($app->pluginEnabled('cooperation') && function_exists('cooperation_rep_figures')) {
                $figures = cooperation_rep_figures($app, (int) $dealer['id']);
            }
            $app->view->plugin('representatives', 'rep-home', [
                'title' => 'پنل نمایندگی',
                'dealer' => $dealer,
                'balance' => dealer_balance($app, (int) $dealer['id']),
                'overdue' => dealer_overdue($app, $dealer),
                'orders' => $app->db->all('SELECT * FROM ak_dealer_orders WHERE dealer_id = ? ORDER BY id DESC LIMIT 8', [(int) $dealer['id']]),
                'circulars' => $app->db->one('SELECT COUNT(*) AS c FROM ak_circular_receipts WHERE dealer_id = ? AND status IN ("sent","seen")', [(int) $dealer['id']]),
                'earned' => $figures['earned'],
                'potential' => $figures['potential'],
            ]);
        });
        $app->router->get('/rep/prices', function () use ($app): void {
            $dealer = dealer_require_rep($app);
            $machines = $this->pricedMachines($app, $dealer);
            $app->view->plugin('representatives', 'rep-prices', ['title' => 'قیمت من', 'rows' => $machines]);
        });
        $app->router->get('/rep/orders', function () use ($app): void {
            $dealer = dealer_require_rep($app);
            $app->view->plugin('representatives', 'rep-order', [
                'title' => 'سفارش از شرکت',
                'dealer' => $dealer,
                'machines' => $this->pricedMachines($app, $dealer),
                'rows' => $app->db->all('SELECT * FROM ak_dealer_orders WHERE dealer_id = ? ORDER BY id DESC', [(int) $dealer['id']]),
            ]);
        });
        $app->router->post('/rep/orders', function () use ($app): void {
            csrf_check();
            $dealer = dealer_require_rep($app);
            $this->placeOrder($app, $dealer);
        });
        $app->router->get('/rep/account', function () use ($app): void {
            $dealer = dealer_require_rep($app);
            $app->view->plugin('representatives', 'rep-account', [
                'title' => 'حساب، چک و مغایرت',
                'dealer' => $dealer,
                'balance' => dealer_balance($app, (int) $dealer['id']),
                'ledger' => $app->db->all('SELECT * FROM ak_dealer_ledger WHERE dealer_id = ? ORDER BY id DESC LIMIT 100', [(int) $dealer['id']]),
                'checks' => $app->db->all('SELECT * FROM ak_dealer_checks WHERE dealer_id = ? ORDER BY id DESC', [(int) $dealer['id']]),
                'payments' => $app->db->all('SELECT * FROM ak_dealer_payments WHERE dealer_id = ? ORDER BY id DESC', [(int) $dealer['id']]),
                'orders' => $app->db->all('SELECT id, number FROM ak_dealer_orders WHERE dealer_id = ? AND status <> "cancelled" ORDER BY id DESC', [(int) $dealer['id']]),
            ]);
        });
        $app->router->post('/rep/payments', function () use ($app): void {
            csrf_check();
            $this->repPayment($app, dealer_require_rep($app));
        });
        $app->router->post('/rep/checks', function () use ($app): void {
            csrf_check();
            $this->repCheck($app, dealer_require_rep($app));
        });
        $app->router->post('/rep/disputes', function () use ($app): void {
            csrf_check();
            $dealer = dealer_require_rep($app);
            $ledgerId = (int) post_string('ledger_id', 12);
            $body = post_string('body', 2000);
            $row = $app->db->one('SELECT id FROM ak_dealer_ledger WHERE id = ? AND dealer_id = ?', [$ledgerId, (int) $dealer['id']]);
            if (!$row || mb_strlen($body) < 5) {
                flash('error', 'ردیف حساب و شرح مغایرت را مشخص کنید.');
                redirect('/rep/account');
            }
            $app->db->run(
                'INSERT INTO ak_dealer_disputes (dealer_id, ledger_id, body, reply, status, created_at) VALUES (?, ?, ?, "", "open", NOW())',
                [(int) $dealer['id'], $ledgerId, $body]
            );
            flash('success', 'اعتراض ثبت شد. مبلغ ردیف تغییر نمی‌کند تا سند اصلاحی جدا صادر شود.');
            redirect('/rep/account');
        });
        $app->router->get('/rep/circulars', function () use ($app): void {
            $dealer = dealer_require_rep($app);
            $app->db->run('UPDATE ak_circular_receipts SET status = "seen", updated_at = NOW() WHERE dealer_id = ? AND status = "sent"', [(int) $dealer['id']]);
            $rows = $app->db->all(
                'SELECT c.*, r.status AS receipt_status, r.report FROM ak_circulars c JOIN ak_circular_receipts r ON r.circular_id = c.id WHERE r.dealer_id = ? ORDER BY c.id DESC',
                [(int) $dealer['id']]
            );
            $app->view->plugin('representatives', 'rep-circulars', ['title' => 'بخشنامه‌ها', 'rows' => $rows]);
        });
        $app->router->post('/rep/circulars/{id}', function (array $params) use ($app): void {
            csrf_check();
            $dealer = dealer_require_rep($app);
            $this->repCircular($app, $dealer, (int) $params['id']);
        });
        $app->router->get('/rep/requests', function () use ($app): void {
            $dealer = dealer_require_rep($app);
            $app->view->plugin('representatives', 'rep-requests', [
                'title' => 'درخواست‌ها',
                'rows' => $app->db->all('SELECT * FROM ak_dealer_requests WHERE dealer_id = ? ORDER BY id DESC', [(int) $dealer['id']]),
                'orders' => $app->db->all('SELECT id, number FROM ak_dealer_orders WHERE dealer_id = ? ORDER BY id DESC', [(int) $dealer['id']]),
            ]);
        });
        $app->router->post('/rep/requests', function () use ($app): void {
            csrf_check();
            $dealer = dealer_require_rep($app);
            $type = post_string('type', 30);
            if (!array_key_exists($type, dealer_request_types())) {
                flash('error', 'نوع درخواست نامعتبر است.');
                redirect('/rep/requests');
            }
            $file = '';
            if ($app->pluginEnabled('attachments') && function_exists('attachment_store')) {
                try {
                    $file = attachment_store($app, 'file');
                } catch (RuntimeException $e) {
                    flash('error', $e->getMessage());
                    redirect('/rep/requests');
                }
            }
            $body = post_string('body', 4000);
            if ($file === '' && mb_strlen($body) < 5) {
                flash('error', 'شرح درخواست را بنویسید.');
                redirect('/rep/requests');
            }
            if ($file !== '' && mb_strlen($body) < 5) {
                $body = 'پیوست';
            }
            if ($app->pluginEnabled('attachments')) {
                $app->db->run(
                    'INSERT INTO ak_dealer_requests (dealer_id, type, order_id, amount, until_date, body, step, status, created_at, updated_at, file)
                     VALUES (?, ?, ?, ?, ?, ?, 0, "open", NOW(), NOW(), ?)',
                    [
                        (int) $dealer['id'],
                        $type,
                        (int) post_string('order_id', 12) ?: null,
                        dealer_money_input('amount'),
                        dealer_date_input('until_date'),
                        $body,
                        $file,
                    ]
                );
            } else {
                $app->db->run(
                    'INSERT INTO ak_dealer_requests (dealer_id, type, order_id, amount, until_date, body, step, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 0, "open", NOW(), NOW())',
                    [
                        (int) $dealer['id'],
                        $type,
                        (int) post_string('order_id', 12) ?: null,
                        dealer_money_input('amount'),
                        dealer_date_input('until_date'),
                        $body,
                    ]
                );
            }
            flash('success', 'درخواست ثبت شد و به مسئول همان مرحله رسید.');
            redirect('/rep/requests');
        });
        $app->router->get('/rep/files/{id}', function (array $params) use ($app): void {
            $dealer = dealer_require_rep($app);
            $file = $app->db->one('SELECT * FROM ak_dealer_files WHERE id = ? AND dealer_id = ?', [(int) $params['id'], (int) $dealer['id']]);
            if (!$file) {
                http_response_code(404);
                exit;
            }
            dealer_send_file($app, (string) $file['filename']);
        });
    }

    /** @return array<string, string> */
    private function settings(App $app): array
    {
        $keys = ['enforce_exclusive', 'allow_resale', 'allow_commission', 'default_credit_days', 'default_commission_percent', 'commission_basis', 'commission_settle', 'return_days'];
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = dealer_setting($app, $key);
        }
        return $values;
    }

    private function dealerForm(App $app, ?array $dealer): void
    {
        $id = (int) ($dealer['id'] ?? 0);
        $app->view->plugin('representatives', 'form', [
            'title' => $dealer ? 'پرونده ' . $dealer['name'] : 'نماینده تازه',
            'dealer' => $dealer,
            'regions' => $app->db->all('SELECT * FROM ak_dealer_regions ORDER BY name'),
            'ledger' => $id ? $app->db->all('SELECT * FROM ak_dealer_ledger WHERE dealer_id = ? ORDER BY id DESC LIMIT 30', [$id]) : [],
            'orders' => $id ? $app->db->all('SELECT * FROM ak_dealer_orders WHERE dealer_id = ? ORDER BY id DESC LIMIT 20', [$id]) : [],
            'files' => $id ? $app->db->all('SELECT * FROM ak_dealer_files WHERE dealer_id = ? ORDER BY id DESC', [$id]) : [],
            'receipts' => $id ? $app->db->all('SELECT r.status, c.title, c.kind FROM ak_circular_receipts r JOIN ak_circulars c ON c.id = r.circular_id WHERE r.dealer_id = ? ORDER BY c.id DESC', [$id]) : [],
            'balance' => $id ? dealer_balance($app, $id) : 0,
            'overdue' => $dealer ? dealer_overdue($app, $dealer) : 0,
            'rule' => ($id ? $app->db->one('SELECT * FROM ak_commission_rules WHERE dealer_id = ? AND line = "" AND level = "" ORDER BY id DESC LIMIT 1', [$id]) : null) ?: [
                'percent' => '',
                'basis' => dealer_setting($app, 'commission_basis', 'sale'),
                'settle_on' => dealer_setting($app, 'commission_settle', 'collection'),
            ],
        ]);
    }

    private function saveDealer(App $app): void
    {
        $id = (int) post_string('id', 12);
        $name = post_string('name', 160);
        $city = post_string('city', 80);
        $cooperation = post_string('cooperation', 20);
        $saleModel = post_string('sale_model', 20);
        $status = post_string('status', 20);
        if ($name === '' || $city === '' || !array_key_exists($cooperation, dealer_cooperations()) || !array_key_exists($saleModel, dealer_sale_models()) || !array_key_exists($status, dealer_statuses())) {
            flash('error', 'نام، شهر، نوع همکاری، مدل فروش و وضعیت را کامل کنید.');
            redirect($id ? '/admin/dealers/' . $id : '/admin/dealers/new');
        }
        if ($saleModel === 'resale' && dealer_setting($app, 'allow_resale', '1') !== '1') {
            flash('error', 'مدل خرید و فروش دوباره در تنظیمات خاموش است.');
            redirect('/admin/dealers/settings');
        }
        if ($saleModel === 'commission' && dealer_setting($app, 'allow_commission', '1') !== '1') {
            flash('error', 'مدل پورسانتی در تنظیمات خاموش است.');
            redirect('/admin/dealers/settings');
        }
        $exclusive = isset($_POST['exclusive_city']) ? 1 : 0;
        $exception = isset($_POST['exclusive_exception']) ? 1 : 0;
        if ($exclusive === 1 && $exception === 0 && dealer_setting($app, 'enforce_exclusive', '1') === '1' && $status === 'active') {
            $other = $app->db->one(
                'SELECT id, name FROM ak_dealers WHERE city = ? AND exclusive_city = 1 AND status = "active" AND id <> ?',
                [$city, $id]
            );
            if ($other) {
                flash('error', 'شهر ' . $city . ' برای ' . $other['name'] . ' انحصاری است. استثنا را فقط شرکت می‌تواند روشن کند.');
                redirect($id ? '/admin/dealers/' . $id : '/admin/dealers/new');
            }
        }
        $lines = $_POST['lines'] ?? [];
        $groups = [];
        if (is_array($lines)) {
            foreach ($lines as $line) {
                if (is_string($line) && array_key_exists($line, dealer_lines())) {
                    $groups[] = $line;
                }
            }
        }
        $current = $id ? $app->db->one('SELECT * FROM ak_dealers WHERE id = ?', [$id]) : null;
        try {
            $userId = $this->linkUser($app, $current, post_string('username', 60), (string) ($_POST['password'] ?? ''), post_string('phone', 20), $name);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect($id ? '/admin/dealers/' . $id : '/admin/dealers/new');
        }
        $params = [
            $userId,
            post_string('kind', 20) === 'company' ? 'company' : 'person',
            $name,
            post_string('national_id', 30),
            normalize_phone(post_string('phone', 20)),
            post_string('province', 80),
            $city,
            $cooperation,
            $saleModel,
            post_string('territory', 240),
            implode(',', $groups),
            $exclusive,
            $exception,
            post_string('level', 40),
            dealer_money_input('credit_limit'),
            max(0, min(365, (int) post_string('credit_days', 4))),
            post_string('settlement', 240),
            dealer_date_input('contract_start'),
            dealer_date_input('contract_end'),
            (int) post_string('region_id', 12) ?: null,
            $status,
        ];
        if ($current) {
            $app->db->run(
                'UPDATE ak_dealers SET user_id=?, kind=?, name=?, national_id=?, phone=?, province=?, city=?, cooperation=?, sale_model=?, territory=?, product_groups=?, exclusive_city=?, exclusive_exception=?, level=?, credit_limit=?, credit_days=?, settlement=?, contract_start=?, contract_end=?, region_id=?, status=? WHERE id=?',
                array_merge($params, [$id])
            );
        } else {
            $code = 'AKN-' . random_int(100000, 999999);
            $app->db->run(
                'INSERT INTO ak_dealers (user_id, kind, name, national_id, phone, province, city, cooperation, sale_model, territory, product_groups, exclusive_city, exclusive_exception, level, credit_limit, credit_days, settlement, contract_start, contract_end, region_id, status, code, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                array_merge($params, [$code])
            );
            $id = (int) $app->db->id();
        }
        try {
            $filename = dealer_store_upload($app, 'document');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/admin/dealers/' . $id);
        }
        if ($filename !== '') {
            $app->db->run(
                'INSERT INTO ak_dealer_files (dealer_id, kind, title, filename, created_at) VALUES (?, "contract", ?, ?, NOW())',
                [$id, post_string('document_title', 160) ?: 'مدرک نمایندگی', $filename]
            );
        }
        $rawPercent = post_string('commission_percent', 3);
        $basis = post_string('commission_basis', 12) === 'gross' ? 'gross' : 'sale';
        $settle = post_string('commission_settle', 20) === 'sale' ? 'sale' : 'collection';
        if ($saleModel !== 'commission') {
            ak_audit($app, 'نماینده', $name, 'خرید از شرکت و فروش دوباره؛ بدون پورسانت');
        } elseif ($rawPercent === '') {
            $app->db->run('DELETE FROM ak_commission_rules WHERE dealer_id = ? AND line = "" AND level = ""', [$id]);
            ak_audit($app, 'نماینده', $name, 'درصد پیش‌فرض شرکت');
        } elseif (!preg_match('/^\d{1,3}$/', $rawPercent) || (int) $rawPercent > 100) {
            flash('error', 'درصد پورسانت باید عدد صفر تا ۱۰۰ باشد. برای پیش‌فرض شرکت، فیلد را خالی بگذارید.');
            redirect('/admin/dealers/' . $id);
        } else {
            $percent = (int) $rawPercent;
            $rule = $app->db->one('SELECT id FROM ak_commission_rules WHERE dealer_id = ? AND line = "" AND level = "" ORDER BY id DESC LIMIT 1', [$id]);
            if ($rule) {
                $app->db->run(
                    'UPDATE ak_commission_rules SET percent = ?, basis = ?, settle_on = ? WHERE id = ?',
                    [$percent, $basis, $settle, (int) $rule['id']]
                );
            } else {
                $app->db->run(
                    'INSERT INTO ak_commission_rules (dealer_id, level, line, percent, basis, settle_on) VALUES (?, "", "", ?, ?, ?)',
                    [$id, $percent, $basis, $settle]
                );
            }
            ak_audit($app, 'نماینده', $name, 'درصد ' . (string) $percent . ' از ' . ($basis === 'gross' ? 'سود' : 'فروش'));
        }
        flash('success', 'پرونده نمایندگی ذخیره شد.');
        redirect('/admin/dealers/' . $id);
    }

    private function linkUser(App $app, ?array $current, string $username, string $password, string $phone, string $name): ?int
    {
        $phone = normalize_phone($phone);
        $userId = (int) ($current['user_id'] ?? 0);
        if ($username === '' && $password === '') {
            return $userId > 0 ? $userId : null;
        }
        if ($username !== '' && !preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
            throw new RuntimeException('نام کاربری نماینده معتبر نیست.');
        }
        if ($password !== '' && mb_strlen($password) < 8) {
            throw new RuntimeException('رمز ورود نماینده حداقل ۸ نویسه باشد.');
        }
        if ($userId > 0) {
            try {
                if ($username !== '') {
                    $app->db->run('UPDATE ak_users SET name = ?, username = ?, phone = ?, role = "representative", active = 1 WHERE id = ?', [$name, $username, $phone === '' ? null : $phone, $userId]);
                }
                if ($password !== '') {
                    $app->db->run('UPDATE ak_users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $userId]);
                }
            } catch (PDOException) {
                throw new RuntimeException('نام کاربری یا موبایل ورود تکراری است.');
            }
            return $userId;
        }
        if ($username === '' || $password === '') {
            return null;
        }
        try {
            $app->db->run(
                'INSERT INTO ak_users (name, username, phone, password_hash, role, active, created_at) VALUES (?, ?, ?, ?, "representative", 1, NOW())',
                [$name, $username, $phone === '' ? null : $phone, password_hash($password, PASSWORD_DEFAULT)]
            );
        } catch (PDOException) {
            throw new RuntimeException('نام کاربری یا موبایل ورود تکراری است.');
        }
        return (int) $app->db->id();
    }

    /** @return list<array<string, mixed>> */
    private function pricedMachines(App $app, array $dealer): array
    {
        $allowed = array_filter(explode(',', (string) $dealer['product_groups']));
        $machines = $app->db->all('SELECT * FROM ak_machines WHERE active = 1 ORDER BY sort_order, id');
        $rows = [];
        foreach ($machines as $machine) {
            if ($allowed !== [] && !in_array($machine['line'], $allowed, true)) {
                continue;
            }
            if ($app->pluginEnabled('cooperation') && function_exists('cooperation_quote')) {
                $quote = cooperation_quote($app, $dealer, (int) $machine['id']);
                if (!$quote) {
                    continue;
                }
                $machine['unit_price'] = $quote['price'];
                $machine['unit_cost'] = $quote['cost'];
                $machine['retail_price'] = $quote['retail'];
                $machine['margin_label'] = $quote['margin_label'];
                $machine['markup_label'] = $quote['markup_label'];
                $machine['quote_source'] = $quote['source'];
                $rows[] = $machine;
                continue;
            }
            $price = dealer_price_for($app, $dealer, (int) $machine['id']);
            if ($price) {
                $machine['unit_price'] = $price['price'];
                $machine['unit_cost'] = $price['cost'];
                $rows[] = $machine;
            }
        }
        return $rows;
    }

    private function placeOrder(App $app, array $dealer): void
    {
        $ids = $_POST['machine_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        if (!is_array($ids) || !is_array($qtys)) {
            flash('error', 'حداقل یک مدل را انتخاب کنید.');
            redirect('/rep/orders');
        }
        $items = [];
        $total = 0;
        foreach ($ids as $index => $machineId) {
            $qty = (int) ($qtys[$index] ?? 0);
            $machineId = (int) $machineId;
            if ($machineId < 1 || $qty < 1) {
                continue;
            }
            $allowed = false;
            foreach ($this->pricedMachines($app, $dealer) as $machine) {
                if ((int) $machine['id'] === $machineId) {
                    $items[] = $machine + ['qty' => $qty];
                    $total += $qty * (int) $machine['unit_price'];
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed && $machineId > 0 && $qty > 0) {
                flash('error', 'یکی از مدل‌ها در محدوده یا قیمت این نماینده نیست.');
                redirect('/rep/orders');
            }
        }
        if ($items === [] || $total < 1) {
            flash('error', 'مدل، تعداد و قیمت لازم است. اگر قیمت خالی است شرکت باید آن را ثبت کند.');
            redirect('/rep/orders');
        }
        $mode = post_string('pay_mode', 20);
        if (!in_array($mode, ['cash', 'check', 'credit', 'mixed'], true)) {
            $mode = 'cash';
        }
        $cashPct = (int) post_string('cash_percent', 3);
        $checkPct = (int) post_string('check_percent', 3);
        $creditPct = (int) post_string('credit_percent', 3);
        if ($mode === 'mixed' && $cashPct + $checkPct + $creditPct !== 100) {
            flash('error', 'در پرداخت ترکیبی جمع درصدها باید ۱۰۰ باشد.');
            redirect('/rep/orders');
        }
        [$cash, $check, $credit] = dealer_split($total, $mode, $cashPct, $checkPct, $creditPct);
        $block = dealer_order_block($app, $dealer, $credit);
        if ($block !== null) {
            flash('error', $block);
            redirect('/rep/orders');
        }
        $days = $credit > 0 ? (int) $dealer['credit_days'] : 0;
        $number = 'AKO-' . date('ymd') . '-' . random_int(100, 999);
        $app->db->transaction(function () use ($app, $dealer, $number, $mode, $cash, $check, $credit, $days, $total, $items): void {
            $app->db->run(
                'INSERT INTO ak_dealer_orders (number, dealer_id, status, pay_mode, cash_amount, check_amount, credit_amount, credit_days, total, note, created_at)
                 VALUES (?, ?, "submitted", ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$number, (int) $dealer['id'], $mode, $cash, $check, $credit, $days, $total, post_string('note', 500)]
            );
            $orderId = (int) $app->db->id();
            foreach ($items as $item) {
                if ($app->pluginEnabled('cooperation')) {
                    $retail = (int) ($item['retail_price'] ?? 0);
                    $potential = 0;
                    if ((string) ($dealer['sale_model'] ?? '') === 'resale' && $retail > 0) {
                        $potential = ($retail - (int) $item['unit_price']) * (int) $item['qty'];
                    }
                    $app->db->run(
                        'INSERT INTO ak_dealer_order_items (order_id, machine_id, title, qty, unit_price, unit_cost, retail_price, potential) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [$orderId, (int) $item['id'], (string) $item['name'], (int) $item['qty'], (int) $item['unit_price'], (int) $item['unit_cost'], $retail, $potential]
                    );
                } else {
                    $app->db->run(
                        'INSERT INTO ak_dealer_order_items (order_id, machine_id, title, qty, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?, ?)',
                        [$orderId, (int) $item['id'], (string) $item['name'], (int) $item['qty'], (int) $item['unit_price'], (int) $item['unit_cost']]
                    );
                }
            }
        });
        flash('success', 'سفارش ' . $number . ' برای تأیید شرکت ثبت شد. تا قبل از تأیید در حساب بدهکار نمی‌شود.');
        redirect('/rep/orders');
    }

    private function repPayment(App $app, array $dealer): void
    {
        $amount = dealer_money_input('amount');
        $method = post_string('method', 20) === 'bank' ? 'bank' : 'cash';
        $orderId = (int) post_string('order_id', 12);
        if ($amount < 1) {
            flash('error', 'مبلغ پرداخت را وارد کنید.');
            redirect('/rep/account');
        }
        try {
            $receipt = dealer_store_upload($app, 'receipt');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/rep/account');
        }
        $app->db->run(
            'INSERT INTO ak_dealer_payments (dealer_id, order_id, amount, method, note, receipt, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "pending", NOW())',
            [(int) $dealer['id'], $orderId > 0 ? $orderId : null, $amount, $method, post_string('note', 240), $receipt]
        );
        flash('success', 'رسید ثبت شد و تا تأیید حسابدار در مانده حساب اثر ندارد.');
        redirect('/rep/account');
    }

    private function repCheck(App $app, array $dealer): void
    {
        $amount = dealer_money_input('amount');
        $due = dealer_date_input('due_date');
        $bank = post_string('bank', 80);
        $serial = post_string('serial', 40);
        if ($amount < 1 || $due === null || $bank === '' || $serial === '') {
            flash('error', 'مبلغ، بانک، سریال و سررسید چک لازم است.');
            redirect('/rep/account');
        }
        try {
            $image = dealer_store_upload($app, 'image');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/rep/account');
        }
        $orderId = (int) post_string('order_id', 12);
        $app->db->run(
            'INSERT INTO ak_dealer_checks (dealer_id, order_id, amount, bank, serial, due_date, status, image, note, created_at) VALUES (?, ?, ?, ?, ?, ?, "pending", ?, ?, NOW())',
            [(int) $dealer['id'], $orderId > 0 ? $orderId : null, $amount, $bank, $serial, $due, $image, post_string('note', 240)]
        );
        flash('success', 'چک ثبت شد. تا وصول، بستانکار حساب نمی‌شود.');
        redirect('/rep/account');
    }

    private function decidePayment(App $app, int $id, int $userId): void
    {
        $payment = $app->db->one('SELECT * FROM ak_dealer_payments WHERE id = ?', [$id]);
        if (!$payment || $payment['status'] !== 'pending') {
            redirect('/admin/dealers/money');
        }
        $decision = post_string('decision', 20);
        if ($decision === 'reject') {
            $app->db->run('UPDATE ak_dealer_payments SET status = "rejected" WHERE id = ?', [$id]);
            flash('success', 'رسید رد شد.');
            redirect('/admin/dealers/money');
        }
        $app->db->transaction(function () use ($app, $payment, $userId): void {
            $app->db->run('UPDATE ak_dealer_payments SET status = "confirmed" WHERE id = ?', [(int) $payment['id']]);
            dealer_post($app, (int) $payment['dealer_id'], 'credit', (int) $payment['amount'], 'payment', 'payment', (int) $payment['id'], 'دریافت تأییدشده', $userId);
        });
        if (!empty($payment['order_id'])) {
            dealer_earn_due($app, (int) $payment['order_id'], $userId);
        }
        flash('success', 'دریافت تأیید شد و بستانکار حساب شد.');
        redirect('/admin/dealers/money');
    }

    private function decideCheck(App $app, int $id, int $userId): void
    {
        $check = $app->db->one('SELECT * FROM ak_dealer_checks WHERE id = ?', [$id]);
        if (!$check) {
            redirect('/admin/dealers/money');
        }
        $decision = post_string('decision', 20);
        if ($decision === 'clear' && $check['status'] === 'pending') {
            $app->db->transaction(function () use ($app, $check, $userId): void {
                $app->db->run('UPDATE ak_dealer_checks SET status = "cleared" WHERE id = ?', [(int) $check['id']]);
                dealer_post($app, (int) $check['dealer_id'], 'credit', (int) $check['amount'], 'check', 'check', (int) $check['id'], 'وصول چک ' . $check['serial'], $userId);
            });
            flash('success', 'چک وصول شد و در حساب بستانکار گردید.');
        } elseif ($decision === 'bounce' && $check['status'] === 'pending') {
            $app->db->run('UPDATE ak_dealer_checks SET status = "bounced" WHERE id = ?', [$id]);
            flash('success', 'چک برگشت خورد و چون هنوز وصول نشده بود، سند بستانکار نخورد.');
        } elseif ($decision === 'bounce' && $check['status'] === 'cleared') {
            $app->db->transaction(function () use ($app, $check, $userId): void {
                $app->db->run('UPDATE ak_dealer_checks SET status = "bounced" WHERE id = ?', [(int) $check['id']]);
                dealer_post($app, (int) $check['dealer_id'], 'debit', (int) $check['amount'], 'check_reversal', 'check', (int) $check['id'], 'برگشت چک وصول‌شده ' . $check['serial'], $userId);
            });
            flash('success', 'برگشت چک با سند بدهکار اصلاح شد.');
        }
        redirect('/admin/dealers/money');
    }

    private function publishCircular(App $app, int $userId): void
    {
        $title = post_string('title', 180);
        $audience = post_string('audience', 20);
        $file = '';
        if ($app->pluginEnabled('attachments') && function_exists('attachment_store')) {
            try {
                $file = attachment_store($app, 'file');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/admin/dealers/circulars');
            }
        }
        $body = post_string('body', 8000);
        if ($title === '' || !in_array($audience, ['all', 'province', 'selected'], true) || ($file === '' && mb_strlen($body) < 5)) {
            flash('error', 'عنوان، متن و مخاطب بخشنامه را کامل کنید.');
            redirect('/admin/dealers/circulars');
        }
        if ($file !== '' && mb_strlen($body) < 5) {
            $body = 'پیوست';
        }
        $province = post_string('province', 80);
        $kind = post_string('kind', 20) === 'law' ? 'law' : 'circular';
        $priority = post_string('priority', 20);
        if (!in_array($priority, ['normal', 'high', 'urgent'], true)) {
            $priority = 'normal';
        }
        if ($app->pluginEnabled('attachments')) {
            $app->db->run(
                'INSERT INTO ak_circulars (title, body, kind, priority, audience, province, due_date, created_by, created_at, file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)',
                [$title, $body, $kind, $priority, $audience, $province, dealer_date_input('due_date'), $userId, $file]
            );
        } else {
            $app->db->run(
                'INSERT INTO ak_circulars (title, body, kind, priority, audience, province, due_date, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$title, $body, $kind, $priority, $audience, $province, dealer_date_input('due_date'), $userId]
            );
        }
        $circularId = (int) $app->db->id();
        $selected = $_POST['dealer_ids'] ?? [];
        $selectedIds = [];
        if (is_array($selected)) {
            foreach ($selected as $item) {
                $selectedIds[] = (int) $item;
            }
        }
        $dealers = $app->db->all('SELECT id, province FROM ak_dealers WHERE status = "active"');
        foreach ($dealers as $dealer) {
            $include = $audience === 'all'
                || ($audience === 'province' && $province !== '' && $dealer['province'] === $province)
                || ($audience === 'selected' && in_array((int) $dealer['id'], $selectedIds, true));
            if (!$include) {
                continue;
            }
            $app->db->run(
                'INSERT IGNORE INTO ak_circular_receipts (circular_id, dealer_id, status, report, updated_at) VALUES (?, ?, "sent", "", NOW())',
                [$circularId, (int) $dealer['id']]
            );
        }
        flash('success', 'بخشنامه ارسال شد. پذیرش هر نماینده جدا ثبت می‌شود.');
        redirect('/admin/dealers/circulars');
    }

    private function repCircular(App $app, array $dealer, int $circularId): void
    {
        $receipt = $app->db->one('SELECT * FROM ak_circular_receipts WHERE circular_id = ? AND dealer_id = ?', [$circularId, (int) $dealer['id']]);
        if (!$receipt) {
            redirect('/rep/circulars');
        }
        $next = post_string('status', 20);
        $map = ['seen' => 'acked', 'acked' => 'doing', 'doing' => 'done'];
        if (($map[$receipt['status']] ?? '') !== $next && !($receipt['status'] === 'sent' && $next === 'acked')) {
            flash('error', 'این مرحله مجاز نیست.');
            redirect('/rep/circulars');
        }
        $report = post_string('report', 4000);
        if ($next === 'done' && mb_strlen($report) < 3) {
            flash('error', 'برای اعلام انجام، گزارش یا شرح مدرک را بنویسید.');
            redirect('/rep/circulars');
        }
        $app->db->run(
            'UPDATE ak_circular_receipts SET status = ?, report = ?, updated_at = NOW() WHERE id = ?',
            [$next, $report !== '' ? $report : (string) $receipt['report'], (int) $receipt['id']]
        );
        flash('success', 'وضعیت بخشنامه ثبت شد.');
        redirect('/rep/circulars');
    }

    private function stepRequest(App $app, int $id, array $user): void
    {
        $scope = dealer_scope($user);
        $request = $app->db->one(
            'SELECT r.* FROM ak_dealer_requests r JOIN ak_dealers d ON d.id = r.dealer_id WHERE r.id = ?' . $scope['sql'],
            array_merge([$id], $scope['params'])
        );
        if (!$request || $request['status'] !== 'open') {
            redirect('/admin/dealers/requests');
        }
        $flow = dealer_request_flow((string) $request['type']);
        $step = (int) $request['step'];
        $expected = $flow[$step] ?? 'admin';
        $role = (string) $user['role'];
        if ($role !== 'admin' && $role !== $expected) {
            flash('error', 'این مرحله برای نقش ' . (ak_role_labels()[$expected] ?? $expected) . ' است.');
            redirect('/admin/dealers/requests');
        }
        $decision = post_string('decision', 20);
        $response = post_string('response', 500);
        if ($decision === 'reject') {
            $app->db->run('UPDATE ak_dealer_requests SET status = "rejected", response = ?, updated_at = NOW() WHERE id = ?', [$response, $id]);
            flash('success', 'درخواست رد شد.');
            redirect('/admin/dealers/requests');
        }
        $step++;
        if ($step >= count($flow)) {
            $app->db->run('UPDATE ak_dealer_requests SET status = "approved", step = ?, response = ?, updated_at = NOW() WHERE id = ?', [$step, $response, $id]);
            $request['status'] = 'approved';
            dealer_apply_request($app, $request);
            flash('success', 'درخواست تأیید شد و اثر مجاز آن روی پرونده یا سفارش اعمال شد.');
        } else {
            $app->db->run('UPDATE ak_dealer_requests SET step = ?, response = ?, updated_at = NOW() WHERE id = ?', [$step, $response, $id]);
            flash('success', 'مرحله تأیید شد و به مسئول بعدی رفت.');
        }
        redirect('/admin/dealers/requests');
    }
};
