<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'dealership',
            'name' => 'درخواست نمایندگی',
            'description' => 'فرم عمومی درخواست نمایندگی. پذیرش فرم، نماینده یا کاربر نمی‌سازد',
        ];
    }

    public function install(Database $db): void
    {
        dealership_schema($db);
    }

    public function boot(App $app): void
    {
        dealership_schema($app->db);
        ak_menu_ensure($app, '/dealership', 'درخواست نمایندگی', 'فرم همکاری برای متقاضیان نمایندگی', '/about');
        $app->listen('site.nav', function (array $items): array {
            $items[] = ['href' => '/dealership', 'label' => 'درخواست نمایندگی'];
            return $items;
        });
        $roles = ['admin', 'employee', 'region_manager'];
        $app->listen('admin.menu', function (array $items) use ($roles): array {
            $items[] = ['href' => '/admin/dealership', 'label' => 'درخواست نمایندگی', 'roles' => $roles];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = [
                'href' => '/admin/dealership',
                'title' => 'فرم درخواست نمایندگی',
                'text' => 'فرم عمومی در صفحه درخواست نمایندگی است. پذیرش فقط وضعیت فرم را عوض می‌کند و نماینده یا کاربر ورود نمی‌سازد.',
            ];
            return $items;
        });
        $app->router->get('/dealership', function () use ($app): void {
            $app->view->plugin('dealership', 'public', [
                'title' => 'درخواست نمایندگی',
                'files' => $app->pluginEnabled('attachments'),
            ], 'layouts/site');
        });
        $app->router->post('/dealership', function () use ($app): void {
            csrf_check();
            $this->receive($app);
        });
        $app->router->get('/admin/dealership', function () use ($app): void {
            require_role($app, ['admin', 'employee', 'region_manager']);
            $app->view->plugin('dealership', 'index', [
                'title' => 'درخواست نمایندگی',
                'rows' => $app->db->all('SELECT * FROM ak_dealership_forms ORDER BY id DESC LIMIT 200'),
            ]);
        });
        $app->router->post('/admin/dealership', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee', 'region_manager']);
            $id = (int) post_string('id', 12);
            $status = post_string('status', 20);
            if (!in_array($status, ['new', 'reviewing', 'accepted', 'rejected'], true)) {
                redirect('/admin/dealership');
            }
            $app->db->run(
                'UPDATE ak_dealership_forms SET status = ?, note = ? WHERE id = ?',
                [$status, post_string('note', 500), $id]
            );
            ak_audit($app, 'درخواست نمایندگی', dealership_status_label($status), 'شناسه ' . (string) $id);
            flash('success', 'وضعیت فرم ذخیره شد. نماینده یا کاربر ساخته نشد.');
            redirect('/admin/dealership');
        });
    }

    private function receive(App $app): void
    {
        $name = post_string('name', 160);
        $phone = normalize_phone(post_string('phone', 20));
        $city = post_string('city', 80);
        $body = post_string('body', 4000);
        $file = '';
        if ($app->pluginEnabled('attachments') && function_exists('attachment_store')) {
            try {
                $file = attachment_store($app, 'file');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/dealership');
            }
        }
        if (mb_strlen($name) < 2 || !is_mobile($phone) || $city === '' || ($file === '' && mb_strlen($body) < 5)) {
            flash('error', 'نام، موبایل، شهر و شرح درخواست را کامل کنید.');
            redirect('/dealership');
        }
        if ($file !== '' && mb_strlen($body) < 5) {
            $body = 'پیوست';
        }
        $app->db->run(
            'INSERT INTO ak_dealership_forms (name, phone, province, city, shop, body, file, status, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, "new", "", NOW())',
            [$name, $phone, post_string('province', 80), $city, post_string('shop', 160), $body, $file]
        );
        flash('success', 'درخواست ثبت شد. بررسی آن نمایندگی یا نام کاربری نمی‌سازد.');
        redirect('/dealership');
    }
};
