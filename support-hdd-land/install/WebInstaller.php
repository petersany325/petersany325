<?php

namespace HddLand\Install;

use PDO;
use PDOException;
use Throwable;

/**
 * Terminal-free web installer for shared hosting.
 */
class WebInstaller
{
    public const LOCK_REL = 'storage/app/installed.lock';

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?: dirname(__DIR__);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function isInstalled(): bool
    {
        return is_file($this->basePath.'/'.self::LOCK_REL);
    }

    public function lockPath(): string
    {
        return $this->basePath.'/'.self::LOCK_REL;
    }

    /** @return list<array{ok:bool,label:string,detail?:string}> */
    public function checkRequirements(): array
    {
        $ext = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'json', 'curl', 'fileinfo', 'ctype'];
        $rows = [];

        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
        $rows[] = [
            'ok' => $phpOk,
            'label' => 'نسخه PHP ≥ 8.2',
            'detail' => 'فعلی: '.PHP_VERSION,
        ];

        foreach ($ext as $name) {
            $rows[] = [
                'ok' => extension_loaded($name),
                'label' => 'افزونه '.$name,
            ];
        }

        foreach (['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $path = $this->basePath.'/'.$dir;
            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $writable = is_dir($path) && is_writable($path);
            $rows[] = [
                'ok' => $writable,
                'label' => 'قابل نوشتن: '.$dir,
            ];
        }

        $rows[] = [
            'ok' => is_file($this->basePath.'/vendor/autoload.php'),
            'label' => 'وجود vendor (Composer)',
        ];

        return $rows;
    }

    public function requirementsPassed(): bool
    {
        foreach ($this->checkRequirements() as $row) {
            if (! $row['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ok:bool,message:string,payload?:array}
     */
    public function activateLicense(string $licenseKey, string $domain, string $licenseServer): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $domain = strtolower(preg_replace('/^www\./', '', trim($domain)) ?? '');
        $licenseServer = rtrim(trim($licenseServer), '/');

        if ($licenseKey === '' || ! preg_match('/^[A-Z0-9]{4}(?:-[A-Z0-9]{4}){3}$/', $licenseKey)) {
            return ['ok' => false, 'message' => 'فرمت سریال نامعتبر است. مثال: ABCD-1234-EFGH-5678'];
        }
        if ($domain === '' || ! str_contains($domain, '.')) {
            return ['ok' => false, 'message' => 'دامنه معتبر نیست.'];
        }
        if ($licenseServer === '' || ! preg_match('#^https?://#i', $licenseServer)) {
            return ['ok' => false, 'message' => 'آدرس سرور لایسنس نامعتبر است.'];
        }

        // Offline bypass for seller QA only (never document publicly as customer path).
        if (hash_equals('HDDL-DEMO-TEST-0001', $licenseKey)) {
            return [
                'ok' => true,
                'message' => 'لایسنس دمو فعال شد.',
                'payload' => [
                    'license_key' => $licenseKey,
                    'domain' => $domain,
                    'token' => 'demo-'.substr(hash('sha256', $licenseKey.'|'.$domain), 0, 32),
                    'expires_at' => null,
                ],
            ];
        }

        $url = $licenseServer.'/license/activate';
        $body = http_build_query([
            'license_key' => $licenseKey,
            'domain' => $domain,
            'product' => 'hddland-repair',
            'version' => '1.0.0',
        ]);

        $result = $this->httpPost($url, $body);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'ارتباط با سرور لایسنس برقرار نشد.',
            ];
        }

        $json = $result['json'] ?? [];
        if (! ($json['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'سریال نامعتبر یا برای این دامنه فعال نیست.'),
            ];
        }

        return [
            'ok' => true,
            'message' => (string) ($json['message'] ?? 'لایسنس فعال شد.'),
            'payload' => [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'token' => (string) ($json['token'] ?? ''),
                'expires_at' => $json['expires_at'] ?? null,
            ],
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function testDatabase(string $host, string $port, string $database, string $username, string $password): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port ?: '3306');
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 8,
            ]);
            $pdo->exec('USE `'.str_replace('`', '``', $database).'`');
            $pdo->query('SELECT 1');

            return ['ok' => true, 'message' => 'اتصال به دیتابیس موفق بود.'];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => 'خطای دیتابیس: '.$e->getMessage()];
        }
    }

    public function generateAppKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * @param  array<string, string>  $values
     */
    public function writeEnv(array $values): void
    {
        $path = $this->basePath.'/.env';
        $lines = [];
        foreach ($values as $key => $value) {
            // Always quote: passwords often contain # $ ; " and break unquoted .env parsing.
            $v = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], (string) $value);
            $lines[] = $key.'="'.$v.'"';
        }
        $content = implode("\n", $lines)."\n";
        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException('نوشتن فایل .env ممکن نشد. دسترسی پوشه را بررسی کنید.');
        }
        @chmod($path, 0600);
    }

    public function friendlyDbError(string $message): string
    {
        if (str_contains($message, '1045') || stripos($message, 'Access denied') !== false) {
            return 'دسترسی دیتابیس رد شد (Access denied). '
                .'در cPanel → MySQL Databases چک کنید: ۱) نام دیتابیس ۲) نام کاربر ۳) رمز درست باشد '
                .'۴) کاربر به همان دیتابیس با ALL PRIVILEGES وصل شده باشد. '
                .'هاست را هم یک‌بار 127.0.0.1 و یک‌بار localhost امتحان کنید.';
        }
        if (str_contains($message, '1049') || stripos($message, 'Unknown database') !== false) {
            return 'نام دیتابیس یافت نشد. ابتدا دیتابیس را در cPanel بسازید.';
        }
        if (str_contains($message, '2002') || stripos($message, 'Connection refused') !== false) {
            return 'اتصال به MySQL برقرار نشد. مقدار هاست را 127.0.0.1 یا localhost بگذارید.';
        }

        return 'خطای دیتابیس: '.$message;
    }

    /**
     * Create MySQL database + user + ALL privileges via cPanel UAPI (shared hosting).
     *
     * @param  array{
     *   cpanel_host:string,
     *   cpanel_user:string,
     *   cpanel_password:string,
     *   db_name:string,
     *   db_user:string,
     *   db_password:string,
     *   mysql_host?:string,
     *   mysql_port?:string
     * }  $input
     * @return array{ok:bool,message:string,db?:array{host:string,port:string,database:string,username:string,password:string},details?:list<string>}
     */
    public function createDatabaseViaCpanel(array $input): array
    {
        $details = [];
        $cpanelHost = trim((string) ($input['cpanel_host'] ?? ''));
        $cpanelUser = trim((string) ($input['cpanel_user'] ?? ''));
        $cpanelPass = (string) ($input['cpanel_password'] ?? '');
        $dbName = trim((string) ($input['db_name'] ?? ''));
        $dbUser = trim((string) ($input['db_user'] ?? ''));
        $dbPass = (string) ($input['db_password'] ?? '');
        $mysqlHost = trim((string) ($input['mysql_host'] ?? '127.0.0.1')) ?: '127.0.0.1';
        $mysqlPort = trim((string) ($input['mysql_port'] ?? '3306')) ?: '3306';

        if ($cpanelHost === '' || $cpanelUser === '' || $cpanelPass === '') {
            return ['ok' => false, 'message' => 'آدرس/یوزر/رمز cPanel را کامل وارد کنید.'];
        }
        if ($dbName === '' || $dbUser === '' || strlen($dbPass) < 6) {
            return ['ok' => false, 'message' => 'نام دیتابیس، نام کاربر و رمز حداقل ۶ کاراکتری لازم است.'];
        }

        // Allow short names; cPanel usually prefixes with ACCOUNT_
        $dbName = preg_replace('/[^A-Za-z0-9_]/', '', $dbName) ?? '';
        $dbUser = preg_replace('/[^A-Za-z0-9_]/', '', $dbUser) ?? '';
        if ($dbName === '' || $dbUser === '') {
            return ['ok' => false, 'message' => 'نام دیتابیس/کاربر فقط حروف و عدد و _ باشد.'];
        }

        // Strip accidental full prefix if user pasted ACCOUNT_name
        $prefix = $cpanelUser.'_';
        if (str_starts_with($dbName, $prefix)) {
            $dbName = substr($dbName, strlen($prefix));
        }
        if (str_starts_with($dbUser, $prefix)) {
            $dbUser = substr($dbUser, strlen($prefix));
        }

        $cpanelHost = preg_replace('#^https?://#', '', $cpanelHost) ?? $cpanelHost;
        $cpanelHost = rtrim($cpanelHost, '/');
        // If they paste domain:2083 keep host only
        if (str_contains($cpanelHost, ':')) {
            $cpanelHost = explode(':', $cpanelHost)[0];
        }

        $createDb = $this->cpanelUapi($cpanelHost, $cpanelUser, $cpanelPass, 'Mysql', 'create_database', [
            'name' => $dbName,
        ]);
        $details[] = 'create_database: '.($createDb['raw'] ?? ($createDb['message'] ?? ''));
        if (! ($createDb['ok'] ?? false)) {
            $msg = (string) ($createDb['message'] ?? '');
            $low = strtolower($msg);
            if (str_contains($low, 'auth') || str_contains($low, 'login') || str_contains($low, 'permission denied')
                || str_contains($msg, 'ورود') || str_contains($msg, 'احراز')) {
                return [
                    'ok' => false,
                    'message' => 'ورود به cPanel ناموفق بود. یوزر/رمز یا آدرس سرور cPanel را بررسی کنید: '.$msg,
                    'details' => $details,
                ];
            }
            // already exists / other recoverable — continue to privileges + connection test
            $details[] = 'create_database_note: continue after non-ok (often already exists)';
        }

        $createUser = $this->cpanelUapi($cpanelHost, $cpanelUser, $cpanelPass, 'Mysql', 'create_user', [
            'name' => $dbUser,
            'password' => $dbPass,
        ]);
        $details[] = 'create_user: '.($createUser['raw'] ?? ($createUser['message'] ?? ''));
        if (! ($createUser['ok'] ?? false)) {
            // User may already exist — sync password so connection test uses the password entered here
            $setPass = $this->cpanelUapi($cpanelHost, $cpanelUser, $cpanelPass, 'Mysql', 'set_password', [
                'user' => $dbUser,
                'password' => $dbPass,
            ]);
            $details[] = 'set_password: '.($setPass['raw'] ?? ($setPass['message'] ?? ''));
            if (! ($setPass['ok'] ?? false)) {
                $fullUserEarly = $prefix.$dbUser;
                $setPass2 = $this->cpanelUapi($cpanelHost, $cpanelUser, $cpanelPass, 'Mysql', 'set_password', [
                    'user' => $fullUserEarly,
                    'password' => $dbPass,
                ]);
                $details[] = 'set_password_full: '.($setPass2['raw'] ?? ($setPass2['message'] ?? ''));
            }
            $details[] = 'create_user_note: continue (user may already exist)';
        }

        $fullDb = $prefix.$dbName;
        $fullUser = $prefix.$dbUser;

        $priv = $this->cpanelUapi($cpanelHost, $cpanelUser, $cpanelPass, 'Mysql', 'set_privileges_on_database', [
            'user' => $dbUser,
            'database' => $dbName,
            'privileges' => 'ALL PRIVILEGES',
        ]);
        $details[] = 'set_privileges: '.($priv['raw'] ?? '');
        if (! ($priv['ok'] ?? false)) {
            // retry with full names
            $priv2 = $this->cpanelUapi($cpanelHost, $cpanelUser, $cpanelPass, 'Mysql', 'set_privileges_on_database', [
                'user' => $fullUser,
                'database' => $fullDb,
                'privileges' => 'ALL PRIVILEGES',
            ]);
            $details[] = 'set_privileges_full: '.($priv2['raw'] ?? '');
            if (! ($priv2['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => 'اعطای دسترسی ALL PRIVILEGES ناموفق بود: '.($priv2['message'] ?? $priv['message'] ?? ''),
                    'details' => $details,
                ];
            }
        }

        // Prefer prefixed names for app connection (cPanel standard)
        $candidates = [
            ['database' => $fullDb, 'username' => $fullUser],
            ['database' => $dbName, 'username' => $dbUser],
            ['database' => $fullDb, 'username' => $dbUser],
        ];
        $connected = null;
        foreach ($candidates as $c) {
            $test = $this->testDatabase($mysqlHost, $mysqlPort, $c['database'], $c['username'], $dbPass);
            $details[] = 'test '.$c['username'].'@'.$c['database'].': '.(($test['ok'] ?? false) ? 'OK' : ($test['message'] ?? 'fail'));
            if ($test['ok'] ?? false) {
                $connected = $c;
                break;
            }
            // also try localhost
            if ($mysqlHost === '127.0.0.1') {
                $test2 = $this->testDatabase('localhost', $mysqlPort, $c['database'], $c['username'], $dbPass);
                $details[] = 'test localhost '.$c['username'].'@'.$c['database'].': '.(($test2['ok'] ?? false) ? 'OK' : ($test2['message'] ?? 'fail'));
                if ($test2['ok'] ?? false) {
                    $mysqlHost = 'localhost';
                    $connected = $c;
                    break;
                }
            }
        }

        if (! $connected) {
            return [
                'ok' => false,
                'message' => 'دیتابیس از طریق cPanel ساخته شد ولی اتصال MySQL هنوز برقرار نشد. رمز کاربر دیتابیس را قوی‌تر/بدون کاراکتر عجیب بگذارید و دوباره تلاش کنید.',
                'details' => $details,
            ];
        }

        return [
            'ok' => true,
            'message' => 'دیتابیس و کاربر MySQL ساخته شد و دسترسی کامل داده شد.',
            'db' => [
                'host' => $mysqlHost,
                'port' => $mysqlPort,
                'database' => $connected['database'],
                'username' => $connected['username'],
                'password' => $dbPass,
            ],
            'details' => $details,
        ];
    }

    /**
     * @param  array<string, scalar>  $params
     * @return array{ok:bool,message?:string,raw?:string,data?:mixed}
     */
    private function cpanelUapi(string $host, string $user, string $password, string $module, string $func, array $params = []): array
    {
        $query = http_build_query($params);
        $url = 'https://'.$host.':2083/execute/'.$module.'/'.$func.($query !== '' ? ('?'.$query) : '');

        if (! function_exists('curl_init')) {
            return ['ok' => false, 'message' => 'افزونه curl روی هاست فعال نیست.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $user.':'.$password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'message' => 'ارتباط با cPanel برقرار نشد: '.$err.' — آدرس سرور cPanel را درست وارد کنید (مثلاً server.irandns.com).'];
        }

        $json = json_decode($raw, true);
        if (! is_array($json)) {
            return ['ok' => false, 'message' => 'پاسخ نامعتبر cPanel (HTTP '.$code.'). شاید یوزر/رمز cPanel اشتباه باشد.', 'raw' => substr($raw, 0, 300)];
        }

        $status = (int) ($json['status'] ?? 0);
        if ($status !== 1) {
            $errors = $json['errors'] ?? null;
            $msg = is_array($errors) ? implode(' | ', array_map('strval', $errors)) : (string) ($json['messages'][0] ?? 'خطای cPanel');

            return ['ok' => false, 'message' => $msg, 'raw' => substr($raw, 0, 500), 'data' => $json['data'] ?? null];
        }

        return ['ok' => true, 'raw' => substr($raw, 0, 500), 'data' => $json['data'] ?? null];
    }

    /**
     * Bootstrap Laravel and run migrate + fresh seed + storage link.
     *
     * @param  array<string, mixed>  $admin
     * @param  array<string, mixed>  $license
     * @return array{ok:bool,message:string,details?:list<string>}
     */
    public function runInstall(array $admin, array $license): array
    {
        $details = [];
        try {
            if (! is_file($this->basePath.'/vendor/autoload.php')) {
                return ['ok' => false, 'message' => 'پوشه vendor یافت نشد.'];
            }

            require $this->basePath.'/vendor/autoload.php';
            $app = require $this->basePath.'/bootstrap/app.php';
            /** @var \Illuminate\Contracts\Console\Kernel $kernel */
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            $details[] = 'Laravel bootstrap OK';

            $code = $kernel->call('migrate', ['--force' => true]);
            $details[] = trim($kernel->output()) ?: ('migrate exit '.$code);
            if ($code !== 0) {
                return ['ok' => false, 'message' => 'مایگریشن ناموفق بود.', 'details' => $details];
            }

            // Fresh seed: lookups + admin only
            $this->seedFresh($admin);
            $details[] = 'Seed admin + lookups OK';

            try {
                $kernel->call('storage:link', ['--force' => true]);
                $details[] = trim($kernel->output()) ?: 'storage:link OK';
            } catch (Throwable $e) {
                $details[] = 'storage:link skipped: '.$e->getMessage();
            }

            try {
                $kernel->call('config:clear');
                $kernel->call('route:clear');
                $kernel->call('view:clear');
                $details[] = 'caches cleared';
            } catch (Throwable $e) {
                $details[] = 'cache clear soft-fail: '.$e->getMessage();
            }

            $lock = [
                'installed_at' => date('c'),
                'app_url' => $admin['app_url'] ?? null,
                'license_key' => $license['license_key'] ?? null,
                'domain' => $license['domain'] ?? null,
                'license_token' => $license['token'] ?? null,
            ];
            $dir = dirname($this->lockPath());
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($this->lockPath(), json_encode($lock, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return [
                'ok' => true,
                'message' => 'نصب با موفقیت انجام شد.',
                'details' => $details,
            ];
        } catch (Throwable $e) {
            $details[] = $e->getMessage();

            return [
                'ok' => false,
                'message' => 'نصب ناموفق: '.$e->getMessage(),
                'details' => $details,
            ];
        }
    }

    /** @param  array<string, mixed>  $admin */
    private function seedFresh(array $admin): void
    {
        $name = trim((string) ($admin['name'] ?? 'مدیر'));
        $email = trim((string) ($admin['email'] ?? 'admin@example.com'));
        $phone = preg_replace('/\D+/', '', (string) ($admin['phone'] ?? '09120000000')) ?: '09120000000';
        $password = (string) ($admin['password'] ?? 'password');

        $user = \App\Models\User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => $password,
                'role' => 'admin',
                'permissions' => \App\Support\Permissions::defaultsForRole('admin'),
                'can_login_otp' => true,
                'can_login_password' => true,
                'is_active' => true,
            ]
        );

        $sources = ['اینستاگرام', 'گوگل', 'معرفی دوستان', 'تابلو مغازه', 'سایت'];
        foreach ($sources as $src) {
            \App\Models\ReferralSource::query()->firstOrCreate(['name' => $src]);
        }

        $lookupSeed = [
            'admission_type' => ['حضوری', 'پستی', 'پیک', 'نمایندگی'],
            'service_type' => ['تعمیر', 'بازیابی اطلاعات', 'تعویض قطعه', 'عیب‌یابی', 'نصب سیستم'],
            'repair_type' => ['سخت‌افزاری', 'نرم‌افزاری', 'دیتا ریکاوری', 'گارانتی'],
            'warranty_type' => ['فاقد گارانتی و بیمه', 'گارانتی شرکتی', 'گارانتی تعمیرگاه', 'بیمه'],
            'hdd_capacity' => ['120GB', '250GB', '320GB', '500GB', '1TB', '2TB', '4TB'],
            'brand_model' => ['WD My Passport', 'Seagate Backup Plus', 'Toshiba Canvio', 'Samsung T7', 'Laptop Generic'],
        ];
        foreach ($lookupSeed as $group => $names) {
            foreach ($names as $i => $label) {
                \App\Models\LookupOption::query()->firstOrCreate(
                    ['group_key' => $group, 'name' => $label],
                    ['sort_order' => $i + 1, 'is_active' => true]
                );
            }
        }

        $faults = ['روشن نمی‌شود', 'صدای غیرعادی', 'عدم شناسایی', 'بازیابی اطلاعات', 'آسیب فیزیکی', 'نرم‌افزاری'];
        foreach ($faults as $fault) {
            \App\Models\FaultType::query()->firstOrCreate(['name' => $fault]);
        }

        // Ensure chart of accounts if service exists
        try {
            if (class_exists(\App\Services\AccountingService::class)) {
                $ref = new \ReflectionClass(\App\Services\AccountingService::class);
                if ($ref->hasMethod('ensureDefaults')) {
                    app(\App\Services\AccountingService::class)->ensureDefaults();
                }
            }
        } catch (Throwable) {
        }

        unset($user);
    }

    /**
     * @return array{ok:bool,message?:string,json?:array}
     */
    private function httpPost(string $url, string $body): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false) {
                return ['ok' => false, 'message' => 'خطای شبکه لایسنس: '.$err];
            }
            $json = json_decode($raw, true);
            if (! is_array($json)) {
                return ['ok' => false, 'message' => 'پاسخ نامعتبر سرور لایسنس (HTTP '.$code.').'];
            }

            return ['ok' => true, 'json' => $json];
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 25,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'ارتباط با سرور لایسنس برقرار نشد.'];
        }
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            return ['ok' => false, 'message' => 'پاسخ نامعتبر سرور لایسنس.'];
        }

        return ['ok' => true, 'json' => $json];
    }
}
