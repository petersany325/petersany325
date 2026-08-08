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

    /** Seller site that validates licenses and sends install OTP SMS. */
    public const SELLER_LICENSE_SERVER = 'https://support.hdd-land.ir';

    public const SELLER_PURCHASE_URL = 'https://hdd-land.ir';

    /** OTP SMS is always requested from the seller site (not the customer host). */
    public static function sellerSmsOtpUrl(): string
    {
        return self::SELLER_LICENSE_SERVER.'/license/request-otp';
    }

    public static function sellerConfirmOtpUrl(): string
    {
        return self::SELLER_LICENSE_SERVER.'/license/confirm-otp';
    }

    /**
     * Step A: validate serial and ask seller to SMS an OTP.
     *
     * @return array{ok:bool,message:string,demo?:bool,payload?:array,phone_masked?:string,phone?:string,purchase_url?:string}
     */
    public function requestLicenseOtp(string $licenseKey, string $domain, string $licenseServer = '', string $phone = ''): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $domain = strtolower(preg_replace('/^www\./', '', trim($domain)) ?? '');
        // Always use seller site so SMS goes through HDD Land gateway.
        $licenseServer = self::SELLER_LICENSE_SERVER;
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if ($licenseKey === '' || ! preg_match('/^[A-Z0-9]{4}(?:-[A-Z0-9]{4}){3}$/', $licenseKey)) {
            return ['ok' => false, 'message' => 'فرمت سریال نامعتبر است. مثال: ABCD-1234-EFGH-5678'];
        }
        if ($domain === '' || ! str_contains($domain, '.')) {
            return ['ok' => false, 'message' => 'دامنه معتبر نیست.'];
        }

        // Offline bypass for seller QA only (never document publicly as customer path).
        if (hash_equals('HDDL-DEMO-TEST-0001', $licenseKey)) {
            return [
                'ok' => true,
                'demo' => true,
                'message' => 'لایسنس دمو فعال شد (بدون پیامک).',
                'payload' => [
                    'license_key' => $licenseKey,
                    'domain' => $domain,
                    'token' => 'demo-'.substr(hash('sha256', $licenseKey.'|'.$domain), 0, 32),
                    'plan' => 'دمو',
                    'plan_code' => 'demo',
                    'plan_months' => null,
                    'price_toman' => 0,
                    'activated_at' => date('Y-m-d'),
                    'expires_at' => null,
                ],
            ];
        }

        $url = self::sellerSmsOtpUrl();
        // Never send a customer-entered phone — seller admin owns the SMS destination.
        $body = http_build_query([
            'license_key' => $licenseKey,
            'domain' => $domain,
            'product' => 'hddland-repair',
        ]);

        $result = $this->httpPost($url, $body);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'ارتباط با سرور لایسنس/پیامک سرزمین هارد برقرار نشد.',
                'purchase_url' => self::SELLER_PURCHASE_URL,
            ];
        }

        $json = $result['json'] ?? [];
        if (! ($json['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'سریال نامعتبر است.'),
                'purchase_url' => (string) ($json['purchase_url'] ?? self::SELLER_PURCHASE_URL),
            ];
        }

        return [
            'ok' => true,
            'message' => (string) ($json['message'] ?? 'کد تأیید از سرور سرزمین هارد پیامک شد.'),
            'phone_masked' => (string) ($json['phone_masked'] ?? ''),
            'phone' => $phone,
            'purchase_url' => (string) ($json['purchase_url'] ?? self::SELLER_PURCHASE_URL),
            'sms_url' => $url,
        ];
    }

    /**
     * Step B: confirm SMS code and activate license on seller.
     *
     * @return array{ok:bool,message:string,payload?:array,purchase_url?:string}
     */
    public function confirmLicenseOtp(
        string $licenseKey,
        string $domain,
        string $licenseServer,
        string $phone,
        string $code
    ): array {
        $licenseKey = strtoupper(trim($licenseKey));
        $domain = strtolower(preg_replace('/^www\./', '', trim($domain)) ?? '');
        $licenseServer = self::SELLER_LICENSE_SERVER;
        $phone = preg_replace('/\D+/', '', $phone) ?: '';
        $code = trim($code);

        if ($code === '' || ! preg_match('/^\d{4,8}$/', $code)) {
            return ['ok' => false, 'message' => 'کد تأیید را درست وارد کنید.'];
        }

        $url = self::sellerConfirmOtpUrl();
        $body = http_build_query([
            'license_key' => $licenseKey,
            'domain' => $domain,
            'code' => $code,
            'product' => 'hddland-repair',
            'version' => '1.0.0',
        ]);

        $result = $this->httpPost($url, $body);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'ارتباط با سرور لایسنس سرزمین هارد برقرار نشد.',
                'purchase_url' => self::SELLER_PURCHASE_URL,
            ];
        }

        $json = $result['json'] ?? [];
        if (! ($json['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'کد تأیید یا سریال نامعتبر است.'),
                'purchase_url' => (string) ($json['purchase_url'] ?? self::SELLER_PURCHASE_URL),
            ];
        }

        return [
            'ok' => true,
            'message' => (string) ($json['message'] ?? 'لایسنس فعال شد.'),
            'payload' => [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'token' => (string) ($json['token'] ?? ''),
                'plan' => $json['plan'] ?? null,
                'plan_code' => $json['plan_code'] ?? null,
                'plan_months' => $json['plan_months'] ?? null,
                'price_toman' => $json['price_toman'] ?? null,
                'activated_at' => $json['activated_at'] ?? null,
                'expires_at' => $json['expires_at'] ?? null,
            ],
        ];
    }

    /**
     * Demo / legacy helper — real installs must use OTP steps.
     *
     * @return array{ok:bool,message:string,payload?:array}
     */
    public function activateLicense(string $licenseKey, string $domain, string $licenseServer): array
    {
        $res = $this->requestLicenseOtp($licenseKey, $domain, $licenseServer);
        if (($res['demo'] ?? false) === true) {
            return $res;
        }
        if (($res['ok'] ?? false) === true) {
            return [
                'ok' => false,
                'message' => 'فعال‌سازی فقط پس از تأیید پیامک مجاز است. از مرحله کد تأیید استفاده کنید.',
            ];
        }

        return $res;
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

    /**
     * Try common MySQL host variants used on shared hosting.
     *
     * @return array{ok:bool,message:string,host?:string}
     */
    public function testDatabaseWithHostFallback(string $host, string $port, string $database, string $username, string $password): array
    {
        $hosts = [];
        foreach ([$host, 'localhost', '127.0.0.1'] as $h) {
            $h = trim((string) $h);
            if ($h !== '' && ! in_array($h, $hosts, true)) {
                $hosts[] = $h;
            }
        }

        $last = 'اتصال دیتابیس ناموفق.';
        foreach ($hosts as $h) {
            $res = $this->testDatabase($h, $port, $database, $username, $password);
            if ($res['ok'] ?? false) {
                return ['ok' => true, 'message' => $res['message'], 'host' => $h];
            }
            $last = (string) ($res['message'] ?? $last);
        }

        return ['ok' => false, 'message' => $last];
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
                .'برگردید به مرحله دیتابیس و «ساخت خودکار با cPanel» را بزنید '
                .'(یوزر/رمز ورود cPanel — نه فقط کاربر MySQL). '
                .'اگر دستی می‌زنید: نام کامل DB، کاربر، رمز، و ALL PRIVILEGES را در cPanel چک کنید.';
        }
        if (str_contains($message, '1049') || stripos($message, 'Unknown database') !== false) {
            return 'نام دیتابیس یافت نشد. برگردید به مرحله دیتابیس و ساخت خودکار با cPanel را بزنید.';
        }
        if (str_contains($message, '2002') || stripos($message, 'Connection refused') !== false) {
            return 'اتصال به MySQL برقرار نشد. مقدار هاست را 127.0.0.1 یا localhost بگذارید.';
        }

        return 'خطای دیتابیس: '.$message;
    }

    /** Remove Laravel cached config so a previous failed install cannot override new .env */
    public function clearBootstrapCaches(): void
    {
        foreach ([
            'bootstrap/cache/config.php',
            'bootstrap/cache/routes.php',
            'bootstrap/cache/routes-v7.php',
            'bootstrap/cache/services.php',
            'bootstrap/cache/packages.php',
        ] as $rel) {
            $path = $this->basePath.'/'.$rel;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Force runtime DB credentials (avoids stale dotenv/config cache).
     *
     * @param  array{host?:string,port?:string,database?:string,username?:string,password?:string}  $db
     */
    public function forceDatabaseConfig(array $db): void
    {
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? '3306');
        $database = (string) ($db['database'] ?? '');
        $username = (string) ($db['username'] ?? '');
        $password = (string) ($db['password'] ?? '');

        foreach ([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ] as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        if (class_exists(\Illuminate\Support\Facades\Config::class)) {
            \Illuminate\Support\Facades\Config::set('database.default', 'mysql');
            \Illuminate\Support\Facades\Config::set('database.connections.mysql.host', $host);
            \Illuminate\Support\Facades\Config::set('database.connections.mysql.port', $port);
            \Illuminate\Support\Facades\Config::set('database.connections.mysql.database', $database);
            \Illuminate\Support\Facades\Config::set('database.connections.mysql.username', $username);
            \Illuminate\Support\Facades\Config::set('database.connections.mysql.password', $password);
        }

        if (class_exists(\Illuminate\Support\Facades\DB::class)) {
            try {
                \Illuminate\Support\Facades\DB::purge('mysql');
                \Illuminate\Support\Facades\DB::reconnect('mysql');
            } catch (Throwable $e) {
                // reconnect may fail until credentials are valid; migrate will surface it
            }
        }
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
            ['database' => $dbName, 'username' => $fullUser],
        ];
        $connected = null;
        foreach ($candidates as $c) {
            $test = $this->testDatabaseWithHostFallback($mysqlHost, $mysqlPort, $c['database'], $c['username'], $dbPass);
            $details[] = 'test '.$c['username'].'@'.$c['database'].': '.(($test['ok'] ?? false) ? ('OK host='.($test['host'] ?? '')) : ($test['message'] ?? 'fail'));
            if ($test['ok'] ?? false) {
                $mysqlHost = (string) ($test['host'] ?? $mysqlHost);
                $connected = $c;
                break;
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
     * @param  array{host?:string,port?:string,database?:string,username?:string,password?:string}|null  $db
     * @param  array<string, mixed>  $company
     * @param  array{tmp?:string,name?:string,mime?:string}|null  $logo
     * @return array{ok:bool,message:string,details?:list<string>}
     */
    public function runInstall(array $admin, array $license, ?array $db = null, array $company = [], ?array $logo = null): array
    {
        $details = [];
        try {
            if (! is_file($this->basePath.'/vendor/autoload.php')) {
                return ['ok' => false, 'message' => 'پوشه vendor یافت نشد.'];
            }

            // Critical on shared hosting re-installs: stale config.php keeps old DB password.
            $this->clearBootstrapCaches();
            $details[] = 'bootstrap caches cleared';

            require $this->basePath.'/vendor/autoload.php';
            $app = require $this->basePath.'/bootstrap/app.php';
            /** @var \Illuminate\Contracts\Console\Kernel $kernel */
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            $details[] = 'Laravel bootstrap OK';

            if (is_array($db)) {
                $this->forceDatabaseConfig($db);
                $details[] = 'DB config forced: '.($db['username'] ?? '').'@'.($db['database'] ?? '').' host='.($db['host'] ?? '');
            }

            try {
                $kernel->call('config:clear');
            } catch (Throwable $e) {
                $details[] = 'config:clear soft-fail: '.$e->getMessage();
            }

            $wipe = ! empty($admin['wipe_database']);
            $hasSchema = false;
            try {
                $hasSchema = \Illuminate\Support\Facades\Schema::hasTable('users')
                    || \Illuminate\Support\Facades\Schema::hasTable('migrations');
            } catch (Throwable $e) {
                $details[] = 'schema probe: '.$e->getMessage();
            }

            // Re-install / half-failed install: tables already exist → migrate:fresh
            try {
                if ($wipe || $hasSchema) {
                    $details[] = $hasSchema
                        ? 'existing tables detected — running migrate:fresh'
                        : 'wipe_database requested — running migrate:fresh';
                    $code = $kernel->call('migrate:fresh', ['--force' => true]);
                } else {
                    $code = $kernel->call('migrate', ['--force' => true]);
                }
                $details[] = trim($kernel->output()) ?: ('migrate exit '.$code);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $details[] = 'migrate exception: '.$msg;
                if (str_contains($msg, 'already exists') || str_contains($msg, '42S01') || str_contains($msg, '1050')) {
                    $details[] = 'existing tables — retry migrate:fresh';
                    $code = $kernel->call('migrate:fresh', ['--force' => true]);
                    $details[] = trim($kernel->output()) ?: ('migrate:fresh exit '.$code);
                } else {
                    throw $e;
                }
            }

            // Fallback if plain migrate returned non-zero with "already exists"
            if (($code ?? 1) !== 0 && ! $wipe && ! $hasSchema) {
                $out = trim($kernel->output());
                if (str_contains($out, 'already exists') || str_contains($out, '42S01') || str_contains($out, '1050')) {
                    $details[] = 'migrate failed with existing tables — retry migrate:fresh';
                    $code = $kernel->call('migrate:fresh', ['--force' => true]);
                    $details[] = trim($kernel->output()) ?: ('migrate:fresh exit '.$code);
                }
            }

            if (($code ?? 1) !== 0) {
                return [
                    'ok' => false,
                    'message' => 'مایگریشن ناموفق بود. اگر دیتابیس از نصب قبلی پر است، گزینه «پاک کردن جداول قبلی» را بزنید.',
                    'details' => $details,
                ];
            }

            // Fresh seed: admin + company brand only (menus/lookups left empty)
            $this->seedFresh($admin, $company, $logo);
            $details[] = 'Seed admin + company brand OK (lookups empty)';

            try {
                \App\Support\LicenseStatus::store([
                    'ok' => true,
                    'plan' => $license['plan'] ?? null,
                    'plan_code' => $license['plan_code'] ?? null,
                    'plan_months' => $license['plan_months'] ?? null,
                    'price_toman' => $license['price_toman'] ?? null,
                    'activated_at' => $license['activated_at'] ?? date('Y-m-d'),
                    'expires_at' => $license['expires_at'] ?? null,
                ]);
                $details[] = 'license status snapshot saved';
            } catch (Throwable $e) {
                $details[] = 'license status soft-fail: '.$e->getMessage();
            }

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
                'plan' => $license['plan'] ?? null,
                'plan_months' => $license['plan_months'] ?? null,
                'expires_at' => $license['expires_at'] ?? null,
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
            $msg = $e->getMessage();
            if (str_contains($msg, 'already exists') || str_contains($msg, '42S01') || str_contains($msg, '1050')) {
                $msg = 'جداول دیتابیس از قبل وجود دارد. گزینه «پاک کردن جداول قبلی» را تیک بزنید و دوباره نصب کنید.';
            }

            return [
                'ok' => false,
                'message' => 'نصب ناموفق: '.$msg,
                'details' => $details,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $admin
     * @param  array<string, mixed>  $company
     * @param  array{tmp?:string,name?:string,mime?:string}|null  $logo
     */
    private function seedFresh(array $admin, array $company = [], ?array $logo = null): void
    {
        $name = trim((string) ($admin['name'] ?? 'مدیر'));
        $email = trim((string) ($admin['email'] ?? 'admin@example.com'));
        $phone = preg_replace('/\D+/', '', (string) ($admin['phone'] ?? '09120000000')) ?: '09120000000';
        $password = (string) ($admin['password'] ?? 'password');

        \App\Models\User::query()->updateOrCreate(
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

        // White-label empty shop: wipe migration-seeded menu definitions.
        \App\Models\LookupOption::query()->delete();
        \App\Models\FaultType::query()->delete();
        \App\Models\ReferralSource::query()->delete();
        if (class_exists(\App\Models\DailyLogCategory::class)) {
            \App\Models\DailyLogCategory::query()->delete();
        }
        \App\Models\AppSetting::setValue('cost_approval_services', '[]');

        $shopName = trim((string) ($company['shop_name'] ?? $admin['app_name'] ?? 'تعمیرگاه')) ?: 'تعمیرگاه';
        $tagline = trim((string) ($company['tagline'] ?? 'سیستم مدیریت تعمیرات')) ?: 'سیستم مدیریت تعمیرات';
        $phones = trim((string) ($company['phones'] ?? ''));
        $address = trim((string) ($company['address'] ?? ''));
        $footer = trim((string) ($company['footer'] ?? ('مدیریت تعمیرکاران — '.$shopName)));
        $terms = trim((string) ($company['terms'] ?? ''));

        \App\Models\AppSetting::setValue('invoice_shop_name', $shopName);
        \App\Models\AppSetting::setValue('shop_tagline', $tagline);
        \App\Models\AppSetting::setValue('invoice_phones', $phones);
        \App\Models\AppSetting::setValue('invoice_address', $address);
        \App\Models\AppSetting::setValue('invoice_footer', $footer);
        \App\Models\AppSetting::setValue('invoice_terms', $terms);
        \App\Models\AppSetting::setValue('invoice_show_logo', '1');
        \App\Models\AppSetting::setValue('brand_logo_version', (string) time());

        if (is_array($logo) && ! empty($logo['tmp']) && is_file((string) $logo['tmp'])) {
            $this->installBrandLogo((string) $logo['tmp']);
        }

        $this->writePwaManifests($shopName);
    }

    /** Copy uploaded logo into public brand image slots. */
    public function installBrandLogo(string $tmpPath): void
    {
        $dir = $this->basePath.'/public/images';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $targets = [
            $dir.'/logo.png',
            $dir.'/logo-header.png',
            $dir.'/logo-invoice.png',
            $dir.'/logo-192.png',
            $dir.'/logo-512.png',
            $dir.'/apple-touch-icon.png',
        ];
        foreach ($targets as $target) {
            @copy($tmpPath, $target);
        }
        // favicons best-effort
        @copy($tmpPath, $dir.'/favicon-32.png');
        @copy($tmpPath, $dir.'/favicon-16.png');
    }

    public function writePwaManifests(string $shopName): void
    {
        $pwa = $this->basePath.'/public/pwa';
        if (! is_dir($pwa)) {
            return;
        }
        $staff = [
            'name' => $shopName.' | کارتابل کارمندان',
            'short_name' => mb_substr($shopName, 0, 12),
            'start_url' => '/login',
            'display' => 'standalone',
            'background_color' => '#e8eaee',
            'theme_color' => '#2b3340',
            'lang' => 'fa',
            'dir' => 'rtl',
            'icons' => [
                ['src' => '/images/logo-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/images/logo-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ];
        $portal = [
            'name' => $shopName.' | کارتابل مشتری',
            'short_name' => mb_substr($shopName, 0, 12),
            'start_url' => '/portal/login',
            'display' => 'standalone',
            'background_color' => '#e8eaee',
            'theme_color' => '#2b3340',
            'lang' => 'fa',
            'dir' => 'rtl',
            'icons' => [
                ['src' => '/images/logo-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/images/logo-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ];
        // Preserve extra keys from existing manifests when present
        foreach (['staff-manifest.json' => $staff, 'manifest.json' => $portal] as $file => $payload) {
            $path = $pwa.'/'.$file;
            if (is_file($path)) {
                $old = json_decode((string) file_get_contents($path), true);
                if (is_array($old)) {
                    $payload = array_merge($old, $payload);
                }
            }
            file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
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
