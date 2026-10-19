<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'theme-settings',
            'name' => 'تنظیمات قالب',
            'description' => 'نام، رنگ، تلفن و نشانی سایت',
        ];
    }

    public function install(Database $db): void
    {
        $defaults = [
            'site_name' => 'آسان‌کشت',
            'tagline' => 'ماشین‌آلات صنعتی کشاورزی',
            'phone' => '',
            'address' => '',
            'email' => '',
            'color_oxide' => '#e2b000',
            'color_ink' => '#172033',
            'color_paper' => '#f4f6f8',
            'color_dark' => '#0e1c33',
        ];
        foreach ($defaults as $key => $value) {
            $db->run(
                'INSERT INTO ak_settings (plugin, skey, svalue) VALUES ("theme-settings", ?, ?)
                 ON DUPLICATE KEY UPDATE skey = skey',
                [$key, $value]
            );
        }
    }

    public function boot(App $app): void
    {
        $oxide = strtolower(setting($app, 'theme-settings', 'color_oxide', ''));
        $ink = strtolower(setting($app, 'theme-settings', 'color_ink', ''));
        if ($oxide === '#d24a1a' && ($ink === '' || $ink === '#161814')) {
            save_setting($app, 'theme-settings', 'color_oxide', '#e2b000');
            save_setting($app, 'theme-settings', 'color_ink', '#172033');
            save_setting($app, 'theme-settings', 'color_paper', '#f4f6f8');
            save_setting($app, 'theme-settings', 'color_dark', '#0e1c33');
        }
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/theme', 'label' => 'قالب', 'roles' => ['admin']];
            $items[] = ['href' => '/admin/layout', 'label' => 'چیدمان و فوتر', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('site.toolbar', function (array $items): array {
            if (request_path() === '/') {
                array_unshift($items, ['href' => '/admin/layout', 'label' => 'چیدمان صفحه']);
            }
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = ['href' => '/admin/theme', 'title' => 'قالب', 'text' => 'نام، شعار، رنگ، تلفن، نشانی و ایمیل'];
            $items[] = ['href' => '/admin/layout', 'title' => 'چیدمان و فوتر', 'text' => 'ساختار صفحه اول، نوار اطلاع، ترتیب بخش‌ها و ستون‌های فوتر'];
            return $items;
        });
        $app->router->get('/admin/theme', function () use ($app): void {
            require_role($app, ['admin']);
            $keys = ['site_name', 'tagline', 'phone', 'address', 'email', 'color_oxide', 'color_ink', 'color_paper', 'color_dark'];
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = setting($app, 'theme-settings', $key);
            }
            $app->view->plugin('theme-settings', 'form', ['title' => 'تنظیمات قالب', 'values' => $values]);
        });
        $app->router->post('/admin/theme', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            $text = [
                'site_name' => post_string('site_name', 80),
                'tagline' => post_string('tagline', 160),
                'phone' => post_string('phone', 40),
                'address' => post_string('address', 240),
                'email' => post_string('email', 120),
            ];
            if ($text['site_name'] === '') {
                flash('error', 'نام سایت خالی نماند.');
                redirect('/admin/theme');
            }
            foreach ($text as $key => $value) {
                save_setting($app, 'theme-settings', $key, $value);
            }
            foreach (['color_oxide', 'color_ink', 'color_paper', 'color_dark'] as $key) {
                $color = hex_color(trim((string) ($_POST[$key] ?? '')), '');
                if ($color === '') {
                    flash('error', 'رنگ‌ها را از انتخابگر رنگ بردارید.');
                    redirect('/admin/theme');
                }
                save_setting($app, 'theme-settings', $key, $color);
            }
            flash('success', 'قالب ذخیره شد.');
            redirect('/admin/theme');
        });
        $app->router->get('/admin/layout', function () use ($app): void {
            require_role($app, ['admin']);
            $values = layout_values($app);
            $app->view->plugin('theme-settings', 'layout', [
                'title' => 'چیدمان صفحه اول و فوتر',
                'values' => $values,
                'home' => layout_home_blocks($app),
                'links1' => layout_links($values['footer_links_1']),
                'links2' => layout_links($values['footer_links_2']),
            ]);
        });
        $app->router->post('/admin/layout', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            layout_save($app);
        });
    }
};
