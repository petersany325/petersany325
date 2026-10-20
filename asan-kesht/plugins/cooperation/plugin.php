<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'cooperation',
            'name' => 'قیمت همکاری',
            'description' => 'قیمت همکاری و سطح نمایندگی. اختلاف خرده و همکاری پورسانت نیست و به حساب نماینده نمی‌نشیند',
        ];
    }

    public function install(Database $db): void
    {
        cooperation_seed($db);
        cooperation_extend($db);
    }

    public function boot(App $app): void
    {
        cooperation_extend($app->db);
        $roles = ['admin', 'employee'];
        $app->listen('admin.menu', function (array $items) use ($roles): array {
            $items[] = ['href' => '/admin/dealers/cooperation', 'label' => 'قیمت همکاری', 'roles' => $roles];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = [
                'href' => '/admin/dealers/cooperation',
                'title' => 'قیمت همکاری',
                'text' => 'سطح عادی ۱۰، نقره‌ای ۱۵، طلایی ۲۰ و ویژه ۲۵ درصد از قیمت خرده است. اختلاف قیمت، سود بالقوه است و پورسانت یا بستانکاری حساب نماینده نمی‌شود.',
            ];
            return $items;
        });
        $app->router->get('/admin/dealers/cooperation', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $dealerId = (int) en_digits((string) ($_GET['dealer'] ?? '0'));
            $machineId = (int) en_digits((string) ($_GET['machine'] ?? '0'));
            $quote = null;
            $dealer = null;
            $dealers = [];
            $machines = [];
            try {
                $dealers = $app->db->all('SELECT id, name, level, sale_model FROM ak_dealers ORDER BY name');
                $machines = $app->db->all('SELECT id, name, code FROM ak_machines WHERE active = 1 ORDER BY sort_order, id');
            } catch (Throwable) {
                $dealers = [];
                $machines = [];
            }
            if ($dealerId > 0 && $machineId > 0) {
                try {
                    $dealer = $app->db->one('SELECT * FROM ak_dealers WHERE id = ?', [$dealerId]);
                } catch (Throwable) {
                    $dealer = null;
                }
                if ($dealer && function_exists('cooperation_quote')) {
                    $quote = cooperation_quote($app, $dealer, $machineId);
                }
            }
            $app->view->plugin('cooperation', 'index', [
                'title' => 'قیمت همکاری',
                'levels' => cooperation_levels(),
                'margins' => $this->margins($app),
                'floor' => setting($app, 'cooperation', 'min_company_profit', '0'),
                'dealers' => $dealers,
                'machines' => $machines,
                'dealer_id' => $dealerId,
                'machine_id' => $machineId,
                'quote' => $quote,
                'picked' => $dealer,
            ]);
        });
        $app->router->post('/admin/dealers/cooperation', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            foreach (cooperation_levels() as $key) {
                $value = (int) post_string($key, 3);
                if ($value < 0) {
                    $value = 0;
                }
                if ($value > 90) {
                    $value = 90;
                }
                save_setting($app, 'cooperation', $key, (string) $value);
            }
            save_setting($app, 'cooperation', 'min_company_profit', (string) dealer_money_input('min_company_profit'));
            ak_audit($app, 'قیمت همکاری', 'ذخیره سطح‌ها', '');
            flash('success', 'حاشیه سطح‌ها ذخیره شد. سفارش قبلی و حساب نماینده عوض نمی‌شود.');
            redirect('/admin/dealers/cooperation');
        });
    }

    /** @return array<string, string> */
    private function margins(App $app): array
    {
        $defaults = [
            'margin_normal' => '10',
            'margin_silver' => '15',
            'margin_gold' => '20',
            'margin_special' => '25',
        ];
        $values = [];
        foreach ($defaults as $key => $default) {
            $values[$key] = setting($app, 'cooperation', $key, $default);
        }
        return $values;
    }
};
