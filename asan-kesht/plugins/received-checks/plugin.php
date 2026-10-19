<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'received-checks',
            'name' => 'چک دریافتی مشتری',
            'description' => 'چک دریافتی با بانک، شعبه، صیاد و تاریخ وصول. ثبت چک فروش نیست و تا وصول هم در سود نمی‌آید',
        ];
    }

    public function install(Database $db): void
    {
        received_checks_extend($db);
    }

    public function boot(App $app): void
    {
        $ready = received_checks_extend($app->db);
        $roles = ['admin', 'employee', 'accountant'];
        $app->listen('admin.menu', function (array $items) use ($roles): array {
            $items[] = ['href' => '/admin/received-checks', 'label' => 'چک دریافتی', 'roles' => $roles];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = [
                'href' => '/admin/received-checks',
                'title' => 'چک دریافتی مشتری',
                'text' => 'ثبت چک سند فروش نیست. وصول یا برگشت، درآمد و سود را خودش نمی‌سازد. دسته‌چک پرداختی در منوی چک و دسته‌چک می‌ماند.',
            ];
            return $items;
        });
        $app->router->get('/admin/received-checks', function () use ($app, $ready): void {
            require_role($app, ['admin', 'employee', 'accountant']);
            $rows = [];
            if ($ready) {
                $rows = $app->db->all('SELECT * FROM ak_checks WHERE direction = "in" ORDER BY id DESC LIMIT 80');
            }
            $app->view->plugin('received-checks', 'index', [
                'title' => 'چک دریافتی',
                'rows' => $rows,
                'banks' => received_check_banks(),
                'ready' => $ready,
                'today' => date('Y-m-d'),
            ]);
        });
        $app->router->post('/admin/received-checks', function () use ($app, $ready): void {
            csrf_check();
            require_role($app, ['admin', 'employee', 'accountant']);
            if (!$ready) {
                flash('error', 'جدول چک آماده نیست. افزونه حسابداری باید روشن باشد.');
                redirect('/admin/received-checks');
            }
            $this->store($app);
        });
        $app->router->post('/admin/received-checks/status', function () use ($app, $ready): void {
            csrf_check();
            require_role($app, ['admin', 'employee', 'accountant']);
            if (!$ready) {
                redirect('/admin/received-checks');
            }
            $status = post_string('status', 20);
            if (!in_array($status, ['pending', 'cleared', 'bounced', 'void'], true)) {
                redirect('/admin/received-checks');
            }
            $id = (int) post_string('id', 12);
            $app->db->run('UPDATE ak_checks SET status = ? WHERE id = ? AND direction = "in"', [$status, $id]);
            ak_audit($app, 'چک دریافتی', 'وضعیت ' . $status, 'شناسه ' . (string) $id);
            flash('success', 'وضعیت چک ذخیره شد. این تغییر درآمد یا سود نمی‌سازد.');
            redirect('/admin/received-checks');
        });
    }

    private function store(App $app): void
    {
        $sayad = post_string('sayad', 32);
        $bank = post_string('bank', 80);
        $branch = post_string('branch', 80);
        $number = post_string('number', 40);
        $party = post_string('party_name', 160);
        $received = accounting_date(post_string('received_on', 12));
        $due = accounting_date(post_string('due_on', 12));
        $amount = accounting_amount((string) ($_POST['amount'] ?? ''));
        if (!preg_match('/^\d{16}$/', $sayad)) {
            flash('error', 'شماره صیاد باید دقیقاً ۱۶ رقم باشد.');
            redirect('/admin/received-checks');
        }
        if (!in_array($bank, received_check_banks(), true) || mb_strlen($branch) < 2 || $number === '' || $party === '') {
            flash('error', 'بانک، شعبه، شماره چک و نام مشتری لازم است.');
            redirect('/admin/received-checks');
        }
        if ($received === null || $due === null || $due < $received || $amount < 1) {
            flash('error', 'تاریخ دریافت، تاریخ وصول و مبلغ را درست وارد کنید. وصول نباید قبل از دریافت باشد.');
            redirect('/admin/received-checks');
        }
        $dup = $app->db->one('SELECT id FROM ak_checks WHERE sayad = ? AND status <> "void" LIMIT 1', [$sayad]);
        if ($dup) {
            flash('error', 'این شماره صیاد قبلاً برای چک باطل‌نشده ثبت شده است.');
            redirect('/admin/received-checks');
        }
        $app->db->run(
            'INSERT INTO ak_checks (checkbook_id, direction, number, bank, amount, due_on, party_name, status, note, sayad, branch, received_on, created_at)
             VALUES (NULL, "in", ?, ?, ?, ?, ?, "pending", ?, ?, ?, ?, NOW())',
            [$number, $bank, $amount, $due, $party, post_string('note', 240), $sayad, $branch, $received]
        );
        ak_audit($app, 'چک دریافتی', 'ثبت', $sayad);
        flash('success', 'چک دریافتی ثبت شد. این ثبت سند فروش نیست و تا وصول در سود نمی‌آید. وصول هم خودش درآمد نمی‌سازد.');
        redirect('/admin/received-checks');
    }
};
