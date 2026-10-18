<?php

declare(strict_types=1);

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'catalog',
            'name' => 'کاتالوگ ماشین‌آلات',
            'description' => 'ادوات صنعتی کشاورزی و درخواست پیش‌فاکتور',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_machines (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(40) NOT NULL,
                line VARCHAR(20) NOT NULL,
                name VARCHAR(180) NOT NULL,
                summary VARCHAR(400) NOT NULL,
                hp VARCHAR(80) NOT NULL,
                width VARCHAR(80) NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                UNIQUE KEY code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_quote_requests (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                province VARCHAR(80) NOT NULL,
                model_code VARCHAR(40) NOT NULL,
                tractor VARCHAR(80) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $rows = [
            ['AK-MP-3', 'tillage', 'گاوآهن برگرداندار سه‌خیش', 'شخم اولیه با سه خیش', '۶۵ تا ۹۰', '۹۰ تا ۱۰۵ سانتی‌متر', 10],
            ['AK-MP-4', 'tillage', 'گاوآهن برگرداندار چهارخیش', 'برای تراکتور سنگین‌تر', '۹۰ تا ۱۲۰', '۱۲۰ تا ۱۴۰ سانتی‌متر', 20],
            ['AK-DH-24', 'tillage', 'دیسک تاندوم ۲۴ پره', 'نرم‌کردن خاک پس از شخم', '۷۰ تا ۹۰', 'پره ۵۱ سانتی‌متر', 30],
            ['AK-DO-20', 'tillage', 'دیسک افست ۲۰ پره', 'خاک‌ورزی با دیسک افست', '۸۰ تا ۱۱۰', '۲۰ پره', 40],
            ['AK-CH-11', 'tillage', 'چیزل مرکب ۱۱ شاخه', 'خاک‌ورزی مرکب', '۹۰ تا ۱۳۰', '۱۱ شاخه', 50],
            ['AK-SS-5', 'tillage', 'زیرشکن ۵ شاخه', 'شکستن لایه سخت', '۱۰۰ تا ۱۴۰', 'عمق تا ۵۵ سانتی‌متر', 60],
            ['AK-RT-180', 'tillage', 'روتیواتور ۱۸۰', 'خاک‌ورزی باغ و زراعت', '۵۰ تا ۷۵', '۱۸۰ سانتی‌متر', 70],
            ['AK-SD-21', 'seeding', 'خطی‌کار غلات ۲۱ ردیف', 'کاشت بذر غلات', '۷۰ تا ۱۰۰', 'فاصله ردیف ۱۷٫۵', 80],
            ['AK-PR-4', 'seeding', 'ردیف‌کار پنوماتیک ۴ ردیف', 'کاشت ردیفی', '۷۰ تا ۹۰', '۴ ردیف', 90],
            ['AK-FS-800', 'care', 'کودپاش سانتریفیوژ ۸۰۰ کیلویی', 'پخش کود', '۴۰ تا ۷۰', 'مخزن ۸۰۰ کیلو', 100],
            ['AK-SP-600', 'care', 'سمپاش بوم‌دار ۶۰۰ لیتر', 'سمپاشی ۱۲ متر', '۵۰ تا ۸۰', 'بوم ۱۲ متر', 110],
            ['AK-MS-400', 'care', 'سمپاش توربینی باغی', 'سمپاشی باغ', '۴۰ تا ۷۰', 'مخزن ۴۰۰ لیتر', 120],
            ['AK-RM-165', 'harvest', 'دروگر بشقابی ۱۶۵', 'برداشت علوفه', '۴۵ تا ۶۵', '۱۶۵ سانتی‌متر', 130],
            ['AK-TR-4', 'harvest', 'تریلر ۴ تن', 'حمل محصول', 'متناسب با بار', 'دو محور', 140],
        ];
        foreach ($rows as $row) {
            $db->run(
                'INSERT IGNORE INTO ak_machines (code, line, name, summary, hp, width, active, sort_order) VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
                $row
            );
        }
    }

    public function boot(App $app): void
    {
        $app->listen('site.nav', function (array $items): array {
            array_splice($items, 1, 0, [['href' => '/machines', 'label' => 'ماشین‌ها']]);
            return $items;
        });
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/machines', 'label' => 'ماشین‌ها', 'roles' => ['admin', 'employee']];
            $items[] = ['href' => '/admin/quotes', 'label' => 'پیش‌فاکتورها', 'roles' => ['admin', 'employee']];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_quote_requests WHERE status = "new"');
            $cards[] = ['label' => 'درخواست تازه', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/admin/quotes'];
            return $cards;
        });
        $app->listen('block.render', function (array $payload) use ($app): array {
            $block = $payload['block'] ?? [];
            $type = (string) ($block['type'] ?? '');
            if ($type === 'machines') {
                $limit = max(1, min(12, (int) ($block['data']['limit'] ?? 4)));
                $rows = $app->db->all('SELECT * FROM ak_machines WHERE active = 1 ORDER BY sort_order, id LIMIT ' . $limit);
                $payload['html'] = $this->machineSheets((string) ($block['data']['title'] ?? 'ماشین‌ها'), $rows);
            }
            if ($type === 'quote') {
                $payload['html'] = $this->quoteForm($block);
            }
            return $payload;
        });
        $app->router->get('/machines', function () use ($app): void {
            $line = (string) ($_GET['line'] ?? 'all');
            $allowed = ['all', 'tillage', 'seeding', 'care', 'harvest'];
            if (!in_array($line, $allowed, true)) {
                $line = 'all';
            }
            $sql = 'SELECT * FROM ak_machines WHERE active = 1';
            $params = [];
            if ($line !== 'all') {
                $sql .= ' AND line = ?';
                $params[] = $line;
            }
            $sql .= ' ORDER BY sort_order, id';
            $app->view->plugin('catalog', 'machines', [
                'title' => 'ماشین‌آلات',
                'rows' => $app->db->all($sql, $params),
                'line' => $line,
                'lines' => $this->lines(),
            ], 'layouts/site');
        });
        $app->router->post('/quote', function () use ($app): void {
            csrf_check();
            if (trim((string) ($_POST['website'] ?? '')) !== '') {
                redirect('/contact');
            }
            $name = post_string('name', 120);
            $phone = normalize_phone(post_string('phone', 20));
            $province = post_string('province', 80);
            $model = post_string('model', 40);
            $tractor = post_string('tractor', 80);
            $message = post_string('message', 2000);
            if (mb_strlen($name) < 3 || !is_mobile($phone)) {
                flash('error', 'نام و موبایل معتبر لازم است.');
                redirect('/contact');
            }
            $app->db->run(
                'INSERT INTO ak_quote_requests (name, phone, province, model_code, tractor, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "new", NOW())',
                [$name, $phone, $province, $model, $tractor, $message]
            );
            flash('success', 'درخواست پیش‌فاکتور ثبت شد.');
            redirect('/contact');
        });
        $app->router->get('/admin/machines', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $app->view->plugin('catalog', 'admin-machines', [
                'title' => 'ماشین‌ها',
                'rows' => $app->db->all('SELECT * FROM ak_machines ORDER BY sort_order, id'),
                'lines' => $this->lines(),
            ]);
        });
        $app->router->post('/admin/machines', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $id = (int) post_string('id', 12);
            $code = strtoupper(slugify(post_string('code', 40)));
            $code = str_replace('-', '-', $code);
            $line = post_string('line', 20);
            $name = post_string('name', 180);
            if ($code === '' || $name === '' || !isset($this->lines()[$line])) {
                flash('error', 'کد، نام و خط تولید را کامل کنید.');
                redirect('/admin/machines');
            }
            $params = [$code, $line, $name, post_string('summary', 400), post_string('hp', 80), post_string('width', 80), isset($_POST['active']) ? 1 : 0, (int) post_string('sort_order', 6)];
            if ($id > 0) {
                $params[] = $id;
                $app->db->run('UPDATE ak_machines SET code=?, line=?, name=?, summary=?, hp=?, width=?, active=?, sort_order=? WHERE id=?', $params);
            } else {
                $app->db->run('INSERT INTO ak_machines (code, line, name, summary, hp, width, active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $params);
            }
            flash('success', 'ماشین ذخیره شد.');
            redirect('/admin/machines');
        });
        $app->router->get('/admin/quotes', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $app->view->plugin('catalog', 'quotes', [
                'title' => 'درخواست‌های پیش‌فاکتور',
                'rows' => $app->db->all('SELECT * FROM ak_quote_requests ORDER BY id DESC LIMIT 200'),
                'accounting' => $app->pluginEnabled('accounting'),
            ]);
        });
        $app->router->post('/admin/quotes/status', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin', 'employee']);
            $status = post_string('status', 20);
            if (!in_array($status, ['new', 'reviewing', 'quoted', 'closed'], true)) {
                redirect('/admin/quotes');
            }
            $app->db->run('UPDATE ak_quote_requests SET status = ? WHERE id = ?', [$status, (int) post_string('id', 12)]);
            flash('success', 'وضعیت درخواست عوض شد.');
            redirect('/admin/quotes');
        });
    }

    private function lines(): array
    {
        return [
            'tillage' => 'خاک‌ورزی',
            'seeding' => 'کاشت',
            'care' => 'داشت',
            'harvest' => 'برداشت و حمل',
        ];
    }

    private function machineSheets(string $title, array $rows): string
    {
        $html = '<section class="section"><div class="wrap"><div class="section-head"><h2>' . e($title) . '</h2><a href="' . e(url('/machines')) . '">همه مدل‌ها</a></div><div class="sheets">';
        foreach ($rows as $row) {
            $html .= '<a class="sheet" href="' . e(url('/contact?model=' . rawurlencode((string) $row['code']))) . '"><span class="code">' . e((string) $row['code']) . '</span><div><h3>' . e((string) $row['name']) . '</h3><p>' . e((string) $row['summary']) . '</p></div><dl class="specs"><div><dt>توان</dt><dd>' . e((string) $row['hp']) . '</dd></div><div><dt>عرض</dt><dd>' . e((string) $row['width']) . '</dd></div></dl><span class="linkish">درخواست</span></a>';
        }
        return $html . '</div></div></section>';
    }

    private function quoteForm(array $block): string
    {
        $model = e((string) ($_GET['model'] ?? ''));
        $title = e((string) ($block['data']['title'] ?? 'پیش‌فاکتور'));
        $text = e((string) ($block['data']['text'] ?? ''));
        return '<section class="section"><div class="wrap contact-grid"><div><h2>' . $title . '</h2><p>' . $text . '</p></div><form method="post" action="' . e(url('/quote')) . '">' . csrf_field()
            . '<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off">'
            . '<label>نام <input name="name" required></label><label>موبایل <input name="phone" required></label><label>استان <input name="province"></label>'
            . '<label>کد مدل <input name="model" value="' . $model . '"></label><label>توان تراکتور <input name="tractor"></label><label>توضیح <textarea name="message"></textarea></label>'
            . '<button class="btn" type="submit">ثبت درخواست</button></form></div></section>';
    }
};
