<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'register',
            'name' => 'ثبت‌نام',
            'description' => 'فرم مشتری و درخواست نمایندگی؛ همه گزینه‌ها در مدیریت',
        ];
    }

    public function install(Database $db): void
    {
        register_install($db);
    }

    public function boot(App $app): void
    {
        $app->listen('site.nav', function (array $items) use ($app): array {
            if (setting($app, 'register', 'open', '1') === '1') {
                $items[] = ['href' => '/register', 'label' => 'ثبت‌نام'];
            }
            return $items;
        });
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/register', 'label' => 'ثبت‌نام', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('admin.settings', function (array $items) use ($app): array {
            $pending = $app->db->one('SELECT COUNT(*) AS c FROM ak_registrations WHERE status = "pending"');
            $open = setting($app, 'register', 'open', '1') === '1' ? 'باز' : 'بسته';
            $items[] = [
                'href' => '/admin/register',
                'title' => 'ثبت‌نام',
                'text' => 'فیلدها، متن‌ها، شرایط و صف تأیید. وضعیت فرم: ' . $open . '. در انتظار: ' . fa_digits((string) ($pending['c'] ?? 0)),
            ];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $pending = $app->db->one('SELECT COUNT(*) AS c FROM ak_registrations WHERE status = "pending"');
            $cards[] = [
                'label' => 'ثبت‌نام در انتظار',
                'value' => fa_digits((string) ($pending['c'] ?? 0)),
                'href' => '/admin/register',
                'roles' => ['admin'],
            ];
            return $cards;
        });
        $app->router->get('/register', function () use ($app): void {
            $user = $app->auth->user();
            if ($user) {
                redirect(home_for_role((string) $user['role']));
            }
            $values = register_values($app);
            $lines = [];
            foreach (register_offered_lines($values) as $line) {
                $lines[$line] = register_line_labels()[$line];
            }
            $app->view->plugin('register', 'form', [
                'title' => 'ثبت‌نام',
                'values' => $values,
                'kinds' => register_allowed_kinds($values),
                'fields' => register_field_labels(),
                'lines' => $lines,
                'entities' => register_entity_labels(),
                'cooperations' => register_cooperation_labels(),
                'sale_models' => register_sale_model_labels(),
            ], 'layouts/site');
        });
        $app->router->post('/register', function () use ($app): void {
            csrf_check();
            if ($app->auth->user()) {
                redirect(home_for_role((string) $app->auth->user()['role']));
            }
            register_submit($app);
        });
        $app->router->get('/admin/register', function () use ($app): void {
            require_role($app, ['admin']);
            $app->view->plugin('register', 'admin', [
                'title' => 'تنظیمات ثبت‌نام',
                'values' => register_values($app),
                'fields' => register_field_labels(),
                'lines' => register_line_labels(),
                'entities' => register_entity_labels(),
                'cooperations' => register_cooperation_labels(),
                'sale_models' => register_sale_model_labels(),
                'rows' => $app->db->all('SELECT * FROM ak_registrations ORDER BY id DESC LIMIT 80'),
                'statuses' => register_status_labels(),
                'kinds' => register_kind_labels(),
            ]);
        });
        $app->router->post('/admin/register', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            register_save_settings($app);
        });
        $app->router->post('/admin/register/decide', function () use ($app): void {
            csrf_check();
            $actor = require_role($app, ['admin']);
            register_decide($app, $actor);
        });
    }
};
