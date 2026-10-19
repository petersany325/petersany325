<?php

declare(strict_types=1);

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'sms-login',
            'name' => 'ورود پیامکی کارمند و مشتری',
            'description' => 'کد یکبارمصرف برای مشتریان و کارمندان',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_otp_codes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                purpose VARCHAR(20) NOT NULL,
                name VARCHAR(120) NOT NULL DEFAULT "",
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                ip VARCHAR(45) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX (phone, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function boot(App $app): void
    {
        $app->router->post('/login/otp', function () use ($app): void {
            csrf_check();
            $phone = normalize_phone(post_string('phone', 20));
            $name = post_string('name', 120);
            if (!is_mobile($phone)) {
                flash('error', 'موبایل را با ۰۹ و یازده رقم وارد کنید.');
                redirect('/login');
            }
            $existing = $app->db->one('SELECT * FROM ak_users WHERE phone = ?', [$phone]);
            if (!$existing && $app->pluginEnabled('register')) {
                if (setting($app, 'register', 'open', '1') !== '1') {
                    flash('error', setting($app, 'register', 'closed_message', 'ثبت‌نام بسته است.'));
                    redirect('/login');
                }
                flash('error', 'برای ساخت حساب، فرم ثبت‌نام را پر کنید.');
                redirect('/register');
            }
            if (!$existing && mb_strlen($name) < 3) {
                flash('error', 'برای ساخت حساب مشتری، نام را هم بنویسید.');
                redirect('/login');
            }
            if ($existing && (int) $existing['active'] !== 1) {
                flash('error', 'این حساب غیرفعال است.');
                redirect('/login');
            }
            $recent = $app->db->one(
                'SELECT COUNT(*) AS c FROM ak_otp_codes WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
                [$phone]
            );
            if ((int) ($recent['c'] ?? 0) >= 3) {
                flash('error', 'درخواست کد زیاد بوده. چند دقیقه بعد دوباره بخواهید.');
                redirect('/login');
            }
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            $ipCount = $app->db->one(
                'SELECT COUNT(*) AS c FROM ak_otp_codes WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
                [$ip]
            );
            if ((int) ($ipCount['c'] ?? 0) >= 8) {
                flash('error', 'از این شبکه درخواست زیادی آمده است.');
                redirect('/login');
            }
            $sms = $app->get('sms');
            if (!$sms instanceof NiazpardazClient || !$sms->configured()) {
                flash('error', 'اول در مدیریت، افزونه نیازپرداز را با کلید و شماره خط پر کنید.');
                redirect('/login');
            }
            $code = (string) random_int(10000, 99999);
            $app->db->run('DELETE FROM ak_otp_codes WHERE phone = ?', [$phone]);
            $app->db->run(
                'INSERT INTO ak_otp_codes (phone, code_hash, purpose, name, ip, expires_at, created_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE), NOW())',
                [$phone, password_hash($code, PASSWORD_DEFAULT), $existing ? 'login' : 'register', $name, $ip]
            );
            $result = $sms->send($phone, 'کد ورود آسان‌کشت: ' . $code, 'otp');
            if (!$result['ok']) {
                flash('error', $result['message']);
                redirect('/login');
            }
            flash('success', 'کد به موبایل شما ارسال شد. تا دو دقیقه معتبر است.');
            redirect('/login');
        });

        $app->router->post('/login/verify', function () use ($app): void {
            csrf_check();
            $phone = normalize_phone(post_string('phone', 20));
            $code = en_digits(trim((string) ($_POST['code'] ?? '')));
            $row = $app->db->one(
                'SELECT * FROM ak_otp_codes WHERE phone = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
                [$phone]
            );
            if (!$row || (int) $row['attempts'] >= 5) {
                flash('error', 'کد منقضی یا نامعتبر است.');
                redirect('/login');
            }
            if (!password_verify($code, (string) $row['code_hash'])) {
                $app->db->run('UPDATE ak_otp_codes SET attempts = attempts + 1 WHERE id = ?', [(int) $row['id']]);
                flash('error', 'کد درست نیست.');
                redirect('/login');
            }
            $app->db->run('DELETE FROM ak_otp_codes WHERE phone = ?', [$phone]);
            $user = $app->db->one('SELECT * FROM ak_users WHERE phone = ?', [$phone]);
            if (!$user && $app->pluginEnabled('register')) {
                flash('error', 'حسابی با این موبایل نیست. اول فرم ثبت‌نام را پر کنید.');
                redirect('/register');
            }
            if (!$user) {
                $app->db->run(
                    'INSERT INTO ak_users (name, phone, role, active, created_at) VALUES (?, ?, "customer", 1, NOW())',
                    [$row['name'] !== '' ? $row['name'] : 'مشتری']
                );
                $user = $app->db->one('SELECT * FROM ak_users WHERE id = ?', [(int) $app->db->id()]);
            }
            if (!$user || (int) $user['active'] !== 1) {
                flash('error', 'ورود ممکن نیست.');
                redirect('/login');
            }
            $app->auth->login($user);
            redirect(landing_for_role((string) $user['role']));
        });
    }
};
