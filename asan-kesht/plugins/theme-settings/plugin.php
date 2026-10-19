<?php

declare(strict_types=1);

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'theme-settings',
            'name' => 'تنظیمات قالب',
            'description' => 'نام، تم، رنگ، تلفن و نشانی سایت',
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
            'color_oxide' => '#d24a1a',
            'color_ink' => '#161814',
            'color_paper' => '#f3efe4',
            'color_dark' => '#121410',
            'theme_id' => 'classic',
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
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/theme', 'label' => 'قالب', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = ['href' => '/admin/theme', 'title' => 'قالب', 'text' => 'تم، نام، شعار، رنگ، تلفن، نشانی و ایمیل'];
            return $items;
        });
        $app->router->get('/admin/theme', function () use ($app): void {
            require_role($app, ['admin']);
            $keys = ['site_name', 'tagline', 'phone', 'address', 'email', 'color_oxide', 'color_ink', 'color_paper', 'color_dark', 'theme_id'];
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = setting($app, 'theme-settings', $key);
            }
            $active = (string) $values['theme_id'];
            if ($active === '' || ($active !== 'custom' && !theme_palette($active))) {
                $active = theme_id_for_colors(
                    hex_color((string) $values['color_oxide'], '#d24a1a'),
                    hex_color((string) $values['color_ink'], '#161814'),
                    hex_color((string) $values['color_paper'], '#f3efe4'),
                    hex_color((string) $values['color_dark'], '#121410')
                );
            }
            $app->view->plugin('theme-settings', 'form', [
                'title' => 'تنظیمات قالب',
                'values' => $values,
                'presets' => theme_palettes(),
                'active' => $active,
            ]);
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
            $themeId = post_string('theme', 20);
            $palette = theme_palette($themeId);
            if ($palette) {
                save_setting($app, 'theme-settings', 'theme_id', $themeId);
                save_setting($app, 'theme-settings', 'color_oxide', $palette['oxide']);
                save_setting($app, 'theme-settings', 'color_ink', $palette['ink']);
                save_setting($app, 'theme-settings', 'color_paper', $palette['paper']);
                save_setting($app, 'theme-settings', 'color_dark', $palette['dark']);
                preference_cookie('ak_theme', '');
                flash('success', 'قالب «' . $palette['name'] . '» ذخیره شد.');
                redirect('/admin/theme');
            }
            foreach (['color_oxide', 'color_ink', 'color_paper', 'color_dark'] as $key) {
                $color = hex_color(trim((string) ($_POST[$key] ?? '')), '');
                if ($color === '') {
                    flash('error', 'رنگ‌ها را از انتخابگر رنگ بردارید.');
                    redirect('/admin/theme');
                }
                save_setting($app, 'theme-settings', $key, $color);
            }
            save_setting($app, 'theme-settings', 'theme_id', 'custom');
            preference_cookie('ak_theme', '');
            flash('success', 'رنگ دلخواه ذخیره شد.');
            redirect('/admin/theme');
        });
        $app->router->post('/theme', function (): void {
            csrf_check();
            $themeId = post_string('theme', 20);
            if ($themeId === 'site') {
                preference_cookie('ak_theme', '');
            } elseif (theme_palette($themeId)) {
                preference_cookie('ak_theme', $themeId);
            } else {
                flash('error', 'این تم شناخته نشد.');
            }
            redirect(safe_return_path((string) ($_POST['back'] ?? '/')));
        });
    }
};
