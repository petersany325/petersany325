<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'warranty',
            'name' => 'گارانتی محصولات',
            'description' => 'ثبت مدت گارانتی محصول. این پرونده سند مالی نیست و در سود اثر ندارد',
        ];
    }

    public function install(Database $db): void
    {
        warranty_schema($db);
    }

    public function boot(App $app): void
    {
        warranty_schema($app->db);
        ak_menu_ensure($app, '/warranty', 'گارانتی', 'استعلام مدت گارانتی با سریال یا موبایل', '/about');
        $app->listen('site.nav', function (array $items): array {
            $items[] = ['href' => '/warranty', 'label' => 'گارانتی'];
            return $items;
        });
        $roles = ['admin', 'employee'];
        $app->listen('admin.menu', function (array $items) use ($roles): array {
            $items[] = ['href' => '/admin/warranty', 'label' => 'گارانتی محصولات', 'roles' => $roles];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = [
                'href' => '/admin/warranty',
                'title' => 'گارانتی محصولات',
                'text' => 'ثبت مدت گارانتی است و خودش سند مالی نیست. وضعیت منقضی از روی تاریخ حساب می‌شود و تا ابطال، پرونده پاک نمی‌شود.',
            ];
            return $items;
        });
        $app->router->get('/admin/warranty', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $this->page($app);
        });
        $app->router->post('/admin/warranty', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $this->store($app);
        });
        $app->router->post('/admin/warranty/void', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $id = (int) post_string('id', 12);
            $app->db->run('UPDATE ak_warranties SET status = "void" WHERE id = ? AND status = "active"', [$id]);
            ak_audit($app, 'گارانتی', 'ابطال', 'شناسه ' . (string) $id);
            flash('success', 'گارانتی باطل شد. سند مالی ساخته نشد.');
            redirect('/admin/warranty');
        });
        $app->router->get('/warranty', function () use ($app): void {
            $phone = normalize_phone((string) ($_GET['phone'] ?? ''));
            $serial = trim(en_digits((string) ($_GET['serial'] ?? '')));
            if (mb_strlen($serial) > 80) {
                $serial = mb_substr($serial, 0, 80);
            }
            $error = '';
            $rows = [];
            $asked = $phone !== '' || $serial !== '';
            if ($phone !== '' && !is_mobile($phone)) {
                $error = 'موبایل را کامل و با ۰۹ وارد کنید.';
                $asked = false;
            }
            if ($asked) {
                $rows = $this->lookup($app, $phone, $serial);
            }
            $app->view->plugin('warranty', 'public', [
                'title' => 'گارانتی محصول',
                'phone' => $phone,
                'serial' => $serial,
                'rows' => $rows,
                'error' => $error,
                'asked' => $asked,
            ], 'layouts/site');
        });
    }

    private function page(App $app): void
    {
        $machines = [];
        $dealers = [];
        try {
            $machines = $app->db->all('SELECT id, name, code FROM ak_machines WHERE active = 1 ORDER BY sort_order, id');
        } catch (Throwable) {
            $machines = [];
        }
        try {
            $dealers = $app->db->all('SELECT id, name, code FROM ak_dealers ORDER BY name');
        } catch (Throwable) {
            $dealers = [];
        }
        $app->view->plugin('warranty', 'index', [
            'title' => 'گارانتی محصولات',
            'rows' => warranty_rows($app),
            'machines' => $machines,
            'dealers' => $dealers,
            'today' => date('Y-m-d'),
        ]);
    }

    private function store(App $app): void
    {
        $machineId = (int) post_string('machine_id', 12);
        $serial = post_string('serial', 80);
        $name = post_string('holder_name', 160);
        $phoneRaw = post_string('holder_phone', 20);
        $phone = $phoneRaw === '' ? '' : normalize_phone($phoneRaw);
        $dealerId = (int) post_string('dealer_id', 12);
        $started = accounting_date(post_string('started_on', 12));
        $months = (int) post_string('months', 3);
        if ($machineId < 1 || $name === '' || $started === null || $months < 1 || $months > 120 || ($serial === '' && $phone === '')) {
            flash('error', 'مدل، نام، تاریخ شروع، مدت و حداقل سریال یا موبایل لازم است.');
            redirect('/admin/warranty');
        }
        if ($phone !== '' && !is_mobile($phone)) {
            flash('error', 'موبایل مالک معتبر نیست.');
            redirect('/admin/warranty');
        }
        $machine = $app->db->one('SELECT id FROM ak_machines WHERE id = ?', [$machineId]);
        if (!$machine) {
            flash('error', 'مدل ماشین پیدا نشد.');
            redirect('/admin/warranty');
        }
        if ($dealerId > 0 && !$app->db->one('SELECT id FROM ak_dealers WHERE id = ?', [$dealerId])) {
            $dealerId = 0;
        }
        $app->db->run(
            'INSERT INTO ak_warranties (machine_id, serial, holder_name, holder_phone, dealer_id, started_on, months, note, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "active", NOW())',
            [$machineId, $serial, $name, $phone, $dealerId, $started, $months, post_string('note', 500)]
        );
        ak_audit($app, 'گارانتی', 'ثبت', $name);
        flash('success', 'گارانتی ثبت شد. این ثبت سند فروش یا سود نیست.');
        redirect('/admin/warranty');
    }

    /** @return list<array<string, mixed>> */
    private function lookup(App $app, string $phone, string $serial): array
    {
        $where = [];
        $params = [];
        if ($phone !== '') {
            $where[] = 'w.holder_phone = ?';
            $params[] = $phone;
        }
        if ($serial !== '') {
            $where[] = 'w.serial = ?';
            $params[] = $serial;
        }
        if ($where === []) {
            return [];
        }
        try {
            return $app->db->all(
                'SELECT w.*, m.name AS machine_name FROM ak_warranties w LEFT JOIN ak_machines m ON m.id = w.machine_id WHERE ' . implode(' OR ', $where) . ' ORDER BY w.id DESC LIMIT 20',
                $params
            );
        } catch (Throwable) {
            return [];
        }
    }
};
