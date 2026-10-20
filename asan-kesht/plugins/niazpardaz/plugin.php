<?php

declare(strict_types=1);

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'niazpardaz',
            'name' => 'پنل پیامک نیازپرداز',
            'description' => 'اتصال وب‌سرویس نیازپرداز برای رمز یکبارمصرف و اعلان‌ها',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_sms_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL,
                context VARCHAR(40) NOT NULL,
                ok TINYINT(1) NOT NULL,
                detail VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function boot(App $app): void
    {
        $app->set('sms', new NiazpardazClient($app));
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/sms', 'label' => 'پیامک نیازپرداز', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('admin.settings', function (array $items): array {
            $items[] = ['href' => '/admin/sms', 'title' => 'پیامک نیازپرداز', 'text' => 'کلید، خط فرستنده و پیام آزمایشی. تا وقتی پر نشود کد ورود ارسال نمی‌شود.'];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_sms_logs');
            $cards[] = ['label' => 'پیامک‌های ثبت‌شده', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/admin/sms'];
            return $cards;
        });
        $app->router->get('/admin/sms', function () use ($app): void {
            require_role($app, ['admin']);
            $logs = $app->db->all('SELECT * FROM ak_sms_logs ORDER BY id DESC LIMIT 30');
            $app->view->plugin('niazpardaz', 'settings', [
                'title' => 'پنل پیامک نیازپرداز',
                'logs' => $logs,
                'api_key' => setting($app, 'niazpardaz', 'api_key'),
                'username' => setting($app, 'niazpardaz', 'username'),
                'from_number' => setting($app, 'niazpardaz', 'from_number'),
                'has_password' => setting($app, 'niazpardaz', 'password') !== '',
            ]);
        });
        $app->router->post('/admin/sms', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            save_setting($app, 'niazpardaz', 'api_key', trim((string) ($_POST['api_key'] ?? '')));
            save_setting($app, 'niazpardaz', 'username', trim((string) ($_POST['username'] ?? '')));
            save_setting($app, 'niazpardaz', 'from_number', en_digits(trim((string) ($_POST['from_number'] ?? ''))));
            $password = (string) ($_POST['password'] ?? '');
            if ($password !== '') {
                save_setting($app, 'niazpardaz', 'password', $password);
            }
            flash('success', 'تنظیم پیامک ذخیره شد.');
            redirect('/admin/sms');
        });
        $app->router->post('/admin/sms/test', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            $phone = normalize_phone(post_string('test_phone', 20));
            if (!is_mobile($phone)) {
                flash('error', 'شماره تست باید موبایل باشد.');
                redirect('/admin/sms');
            }
            /** @var NiazpardazClient $sms */
            $sms = $app->get('sms');
            $result = $sms->send($phone, 'تست پیامک آسان‌کشت. اگر این متن را می‌بینید اتصال نیازپرداز برقرار است.', 'test');
            flash($result['ok'] ? 'success' : 'error', $result['message']);
            redirect('/admin/sms');
        });
    }
};

final class NiazpardazClient
{
    public function __construct(private App $app)
    {
    }

    public function configured(): bool
    {
        $from = setting($this->app, 'niazpardaz', 'from_number');
        $key = setting($this->app, 'niazpardaz', 'api_key');
        $user = setting($this->app, 'niazpardaz', 'username');
        return $from !== '' && ($key !== '' || $user !== '');
    }

    /** @return array{ok:bool,message:string} */
    public function send(string $to, string $text, string $context): array
    {
        if (!$this->configured()) {
            $this->log($to, $context, false, 'not-configured');
            return ['ok' => false, 'message' => 'پنل نیازپرداز هنوز تنظیم نشده است.'];
        }
        $from = setting($this->app, 'niazpardaz', 'from_number');
        $apiKey = setting($this->app, 'niazpardaz', 'api_key');
        $username = setting($this->app, 'niazpardaz', 'username');
        $password = setting($this->app, 'niazpardaz', 'password');
        if ($apiKey !== '') {
            $response = $this->postJson(
                'https://login.niazpardaz.ir/api/v2/RestWebApi/SendBatchSms',
                [
                    'fromNumber' => $from,
                    'toNumbers' => $to,
                    'messageContent' => $text,
                    'isFlash' => false,
                    'userName' => $username,
                    'password' => $password,
                ],
                ['X-API-Key: ' . $apiKey]
            );
        } else {
            $response = $this->postJson(
                'https://panel.niazpardaz-sms.com/SMSInOutBox/Send',
                [
                    'UserName' => $username,
                    'Password' => $password,
                    'From' => $from,
                    'To' => $to,
                    'Message' => $text,
                ],
                []
            );
        }
        if ($response['error'] !== '') {
            $this->log($to, $context, false, $response['error']);
            return ['ok' => false, 'message' => 'ارسال پیامک ناموفق بود: ' . $response['error']];
        }
        $ok = $this->isSuccess($response['body']);
        $this->log($to, $context, $ok, mb_substr($response['body'], 0, 180));
        return [
            'ok' => $ok,
            'message' => $ok ? 'پیامک ارسال شد.' : 'نیازپرداز پیام را نپذیرفت. پاسخ در گزارش پیامک است.',
        ];
    }

    private function isSuccess(string $body): bool
    {
        $trim = trim($body);
        if ($trim === '0' || $trim === '1') {
            return $trim === '0';
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return false;
        }
        $code = $data['ResultCode'] ?? $data['resultCode'] ?? $data['status'] ?? null;
        return (string) $code === '0';
    }

    /** @param array<int, string> $headers
     *  @return array{body:string,error:string}
     */
    private function postJson(string $url, array $payload, array $headers): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['body' => '', 'error' => 'json'];
        }
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return ['body' => '', 'error' => 'curl'];
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            $body = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if (!is_string($body)) {
                return ['body' => '', 'error' => $error !== '' ? $error : 'empty'];
            }
            return ['body' => $body, 'error' => ''];
        }
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if (!is_string($body)) {
            return ['body' => '', 'error' => 'http'];
        }
        return ['body' => $body, 'error' => ''];
    }

    private function log(string $phone, string $context, bool $ok, string $detail): void
    {
        $safe = $context === 'otp' ? 'otp' : $detail;
        $this->app->db->run(
            'INSERT INTO ak_sms_logs (phone, context, ok, detail, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$phone, $context, $ok ? 1 : 0, mb_substr($safe, 0, 250)]
        );
    }
};
