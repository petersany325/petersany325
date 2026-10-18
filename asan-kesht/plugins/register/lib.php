<?php

declare(strict_types=1);

function register_install(Database $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_registrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            dealer_id INT UNSIGNED NULL,
            kind VARCHAR(20) NOT NULL,
            entity VARCHAR(20) NOT NULL DEFAULT "person",
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            username VARCHAR(60) NOT NULL DEFAULT "",
            province VARCHAR(80) NOT NULL DEFAULT "",
            city VARCHAR(80) NOT NULL DEFAULT "",
            address VARCHAR(240) NOT NULL DEFAULT "",
            national_id VARCHAR(30) NOT NULL DEFAULT "",
            shop_name VARCHAR(160) NOT NULL DEFAULT "",
            note VARCHAR(1000) NOT NULL DEFAULT "",
            cooperation VARCHAR(20) NOT NULL DEFAULT "",
            sale_model VARCHAR(20) NOT NULL DEFAULT "",
            product_groups VARCHAR(120) NOT NULL DEFAULT "",
            status VARCHAR(20) NOT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT "",
            created_at DATETIME NOT NULL,
            decided_at DATETIME NULL,
            decided_by INT UNSIGNED NULL,
            INDEX status_created (status, created_at),
            INDEX phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    foreach (register_defaults() as $key => $value) {
        $db->run(
            'INSERT INTO ak_settings (plugin, skey, svalue) VALUES ("register", ?, ?)
             ON DUPLICATE KEY UPDATE skey = skey',
            [$key, $value]
        );
    }
}

/** @return array<string, string> */
function register_defaults(): array
{
    return [
        'open' => '1',
        'allow_customer' => '1',
        'allow_representative' => '1',
        'customer_needs_review' => '0',
        'rep_activate' => '0',
        'require_password' => '1',
        'require_terms' => '1',
        'show_username' => '1',
        'require_username' => '0',
        'show_province' => '1',
        'require_province' => '0',
        'show_city' => '1',
        'require_city' => '1',
        'show_address' => '1',
        'require_address' => '0',
        'show_national_id' => '1',
        'require_national_id' => '0',
        'show_shop' => '1',
        'require_shop' => '0',
        'show_note' => '1',
        'require_note' => '0',
        'show_lines' => '1',
        'show_entity' => '0',
        'show_cooperation' => '0',
        'show_sale_model' => '0',
        'min_password' => '8',
        'default_kind' => 'person',
        'default_cooperation' => 'sale',
        'default_sale_model' => 'resale',
        'offered_lines' => 'tillage,seeding,care,harvest',
        'notify_phone' => '',
        'intro' => 'برای خرید، پیگیری سفارش یا درخواست نمایندگی شهرستان این فرم را پر کنید. حساب مدیر و کارمند از اینجا ساخته نمی‌شود.',
        'terms' => 'اطلاعات را درست نوشته‌ام. درخواست نمایندگی تا وقتی شرکت آن را تأیید نکند فعال نمی‌شود و حق سفارش نمی‌دهد.',
        'closed_message' => 'ثبت‌نام فعلاً بسته است. اگر حساب دارید از صفحه ورود استفاده کنید.',
        'success_customer' => 'حساب شما ساخته شد و وارد شدید.',
        'success_review' => 'درخواست شما ثبت شد. پس از بررسی شرکت، ورود باز می‌شود.',
        'success_representative' => 'درخواست نمایندگی ثبت شد. تا تأیید شرکت امکان ورود نیست.',
    ];
}

/** @return list<string> */
function register_flag_keys(): array
{
    return [
        'open', 'allow_customer', 'allow_representative', 'customer_needs_review', 'rep_activate',
        'require_password', 'require_terms', 'show_username', 'require_username', 'show_province',
        'require_province', 'show_city', 'require_city', 'show_address', 'require_address',
        'show_national_id', 'require_national_id', 'show_shop', 'require_shop', 'show_note',
        'require_note', 'show_lines', 'show_entity', 'show_cooperation', 'show_sale_model',
    ];
}

/** @return array<string, string> */
function register_field_labels(): array
{
    return [
        'username' => 'نام کاربری',
        'province' => 'استان',
        'city' => 'شهر',
        'address' => 'نشانی',
        'national_id' => 'کد ملی یا شناسه',
        'shop_name' => 'نام فروشگاه یا نمایندگی',
        'note' => 'توضیح',
    ];
}

/** @return array<string, string> */
function register_values(App $app): array
{
    $values = [];
    foreach (register_defaults() as $key => $default) {
        $values[$key] = setting($app, 'register', $key, $default);
    }
    return $values;
}

function register_flag(array $values, string $key): bool
{
    return ($values[$key] ?? '0') === '1';
}

/** @return array<string, string> */
function register_kind_labels(): array
{
    return [
        'customer' => 'مشتری',
        'representative' => 'درخواست نمایندگی',
    ];
}

/** @return array<string, string> */
function register_status_labels(): array
{
    return [
        'pending' => 'در انتظار بررسی',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
    ];
}

/** @return array<string, string> */
function register_entity_labels(): array
{
    return [
        'person' => 'شخص',
        'company' => 'شرکت',
    ];
}

/** @return array<string, string> */
function register_cooperation_labels(): array
{
    if (function_exists('dealer_cooperations')) {
        return dealer_cooperations();
    }
    return [
        'sale' => 'فروش',
        'service' => 'خدمات',
        'repair' => 'تعمیرات',
        'mixed' => 'ترکیبی',
    ];
}

/** @return array<string, string> */
function register_sale_model_labels(): array
{
    if (function_exists('dealer_sale_models')) {
        return dealer_sale_models();
    }
    return [
        'resale' => 'خرید از شرکت و فروش دوباره',
        'commission' => 'فروش برای شرکت و پورسانت',
    ];
}

/** @return array<string, string> */
function register_line_labels(): array
{
    if (function_exists('dealer_lines')) {
        return dealer_lines();
    }
    return [
        'tillage' => 'خاک‌ورزی',
        'seeding' => 'کاشت',
        'care' => 'داشت',
        'harvest' => 'برداشت و حمل',
    ];
}

/** @return list<string> */
function register_offered_lines(array $values): array
{
    $known = register_line_labels();
    $picked = [];
    foreach (explode(',', (string) ($values['offered_lines'] ?? '')) as $line) {
        $line = trim($line);
        if (array_key_exists($line, $known)) {
            $picked[] = $line;
        }
    }
    return $picked === [] ? array_keys($known) : $picked;
}

function register_min_password(array $values): int
{
    $min = (int) ($values['min_password'] ?? '8');
    return max(6, min(32, $min));
}

/** @return array<string, string> */
function register_allowed_kinds(array $values): array
{
    $kinds = [];
    if (register_flag($values, 'allow_customer')) {
        $kinds['customer'] = 'مشتری';
    }
    if (register_flag($values, 'allow_representative')) {
        $kinds['representative'] = 'درخواست نمایندگی شهرستان';
    }
    return $kinds;
}

function register_dealers_ready(App $app): bool
{
    try {
        $app->db->one('SELECT id FROM ak_dealers LIMIT 1');
        return true;
    } catch (Throwable) {
        return false;
    }
}

function register_sale_allowed(App $app, string $model): bool
{
    if (!function_exists('dealer_setting')) {
        return true;
    }
    if ($model === 'resale') {
        return dealer_setting($app, 'allow_resale', '1') === '1';
    }
    if ($model === 'commission') {
        return dealer_setting($app, 'allow_commission', '1') === '1';
    }
    return false;
}

function register_resolve_sale_model(App $app, array $values, string $posted): string
{
    $wanted = $posted !== '' ? $posted : (string) $values['default_sale_model'];
    if ($wanted !== 'resale' && $wanted !== 'commission') {
        $wanted = 'resale';
    }
    if (register_sale_allowed($app, $wanted)) {
        return $wanted;
    }
    if (register_sale_allowed($app, 'resale')) {
        return 'resale';
    }
    if (register_sale_allowed($app, 'commission')) {
        return 'commission';
    }
    return 'resale';
}

function register_save_settings(App $app): void
{
    $kindsOn = isset($_POST['allow_customer']) || isset($_POST['allow_representative']);
    if (!$kindsOn && isset($_POST['open'])) {
        flash('error', 'حداقل یکی از مشتری یا نمایندگی باید باز باشد، وگرنه ثبت‌نام بسته می‌ماند.');
    }
    foreach (register_flag_keys() as $key) {
        $on = isset($_POST[$key]) ? '1' : '0';
        if ($key === 'open' && !$kindsOn) {
            $on = '0';
        }
        save_setting($app, 'register', $key, $on);
    }
    $texts = [
        'intro' => 2000,
        'terms' => 4000,
        'closed_message' => 500,
        'success_customer' => 500,
        'success_review' => 500,
        'success_representative' => 500,
    ];
    foreach ($texts as $key => $max) {
        save_setting($app, 'register', $key, post_string($key, $max));
    }
    $notify = normalize_phone(post_string('notify_phone', 20));
    if ($notify !== '' && !is_mobile($notify)) {
        flash('error', 'موبایل خبر ثبت‌نام معتبر نیست. خالی یعنی خبر پیامکی نمی‌رود.');
        $notify = setting($app, 'register', 'notify_phone', '');
    }
    save_setting($app, 'register', 'notify_phone', $notify);
    $min = max(6, min(32, (int) post_string('min_password', 2)));
    save_setting($app, 'register', 'min_password', (string) $min);
    $entity = post_string('default_kind', 20) === 'company' ? 'company' : 'person';
    save_setting($app, 'register', 'default_kind', $entity);
    $cooperation = post_string('default_cooperation', 20);
    if (!array_key_exists($cooperation, register_cooperation_labels())) {
        $cooperation = 'sale';
    }
    save_setting($app, 'register', 'default_cooperation', $cooperation);
    $sale = post_string('default_sale_model', 20) === 'commission' ? 'commission' : 'resale';
    save_setting($app, 'register', 'default_sale_model', $sale);
    $lines = $_POST['offered_lines'] ?? [];
    $known = register_line_labels();
    $picked = [];
    if (is_array($lines)) {
        foreach ($lines as $line) {
            if (is_string($line) && array_key_exists($line, $known)) {
                $picked[] = $line;
            }
        }
    }
    save_setting($app, 'register', 'offered_lines', implode(',', $picked === [] ? array_keys($known) : $picked));
    flash('success', 'تنظیمات ثبت‌نام ذخیره شد.');
    redirect('/admin/register');
}

function register_submit(App $app): void
{
    $values = register_values($app);
    if (!register_flag($values, 'open')) {
        flash('error', $values['closed_message'] !== '' ? $values['closed_message'] : 'ثبت‌نام بسته است.');
        redirect('/register');
    }
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        flash('success', $values['success_customer']);
        redirect('/register');
    }
    $kinds = register_allowed_kinds($values);
    $kind = post_string('kind', 20);
    if (!array_key_exists($kind, $kinds)) {
        flash('error', 'نوع ثبت‌نام را انتخاب کنید.');
        redirect('/register');
    }
    $name = post_string('name', 160);
    $phone = normalize_phone(post_string('phone', 20));
    if (mb_strlen($name) < 3 || !is_mobile($phone)) {
        flash('error', 'نام حداقل ۳ نویسه و موبایل با ۰۹ و یازده رقم باشد.');
        redirect('/register');
    }
    if (register_flag($values, 'require_terms') && !isset($_POST['accept_terms'])) {
        flash('error', 'پذیرفتن شرایط لازم است.');
        redirect('/register');
    }
    $fields = register_collect_fields($values);
    if ($fields === null) {
        redirect('/register');
    }
    $password = trim((string) ($_POST['password'] ?? ''));
    $confirm = trim((string) ($_POST['password_confirm'] ?? ''));
    $min = register_min_password($values);
    if (register_flag($values, 'require_password') && $password === '') {
        flash('error', 'رمز را بنویسید.');
        redirect('/register');
    }
    if ($password !== '' && (mb_strlen($password) < $min || $password !== $confirm)) {
        flash('error', 'رمز حداقل ' . fa_digits((string) $min) . ' نویسه باشد و تکرار آن یکی باشد.');
        redirect('/register');
    }
    $username = '';
    if (register_flag($values, 'show_username')) {
        $username = post_string('username', 60);
        if ($username !== '' && !preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
            flash('error', 'نام کاربری فقط حروف انگلیسی، عدد، نقطه، خط و زیرخط، حداقل ۳ نویسه.');
            redirect('/register');
        }
        if (register_flag($values, 'require_username') && $username === '') {
            flash('error', 'نام کاربری را بنویسید.');
            redirect('/register');
        }
    }
    if ($username === '' && $password !== '') {
        $username = $phone;
    }
    $entity = (string) $values['default_kind'];
    if (register_flag($values, 'show_entity')) {
        $postedEntity = post_string('entity', 20);
        if (!array_key_exists($postedEntity, register_entity_labels())) {
            flash('error', 'شخص یا شرکت را انتخاب کنید.');
            redirect('/register');
        }
        $entity = $postedEntity;
    }
    $cooperation = (string) $values['default_cooperation'];
    if ($kind === 'representative' && register_flag($values, 'show_cooperation')) {
        $posted = post_string('cooperation', 20);
        if (!array_key_exists($posted, register_cooperation_labels())) {
            flash('error', 'نوع همکاری را انتخاب کنید.');
            redirect('/register');
        }
        $cooperation = $posted;
    }
    if (!array_key_exists($cooperation, register_cooperation_labels())) {
        $cooperation = 'sale';
    }
    $postedSale = '';
    if ($kind === 'representative' && register_flag($values, 'show_sale_model')) {
        $postedSale = post_string('sale_model', 20);
        if ($postedSale !== 'resale' && $postedSale !== 'commission') {
            flash('error', 'مدل فروش را انتخاب کنید.');
            redirect('/register');
        }
    }
    $saleModel = $kind === 'representative' ? register_resolve_sale_model($app, $values, $postedSale) : '';
    if ($kind === 'representative' && $postedSale !== '' && $postedSale !== $saleModel) {
        flash('error', 'این مدل فروش در تنظیمات نمایندگی خاموش است.');
        redirect('/register');
    }
    $groups = [];
    if ($kind === 'representative' && register_flag($values, 'show_lines')) {
        $postedLines = $_POST['lines'] ?? [];
        $offered = register_offered_lines($values);
        if (is_array($postedLines)) {
            foreach ($postedLines as $line) {
                if (is_string($line) && in_array($line, $offered, true)) {
                    $groups[] = $line;
                }
            }
        }
    }
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $recent = $app->db->one(
        'SELECT COUNT(*) AS c FROM ak_registrations WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
        [$ip]
    );
    if ((int) ($recent['c'] ?? 0) >= 8) {
        flash('error', 'از این شبکه درخواست زیادی آمده است. کمی بعد دوباره بفرستید.');
        redirect('/register');
    }
    $pending = $app->db->one(
        'SELECT id FROM ak_registrations WHERE phone = ? AND status = "pending"',
        [$phone]
    );
    if ($pending) {
        flash('error', 'برای این موبایل یک درخواست در انتظار بررسی است.');
        redirect('/register');
    }
    $taken = $username !== ''
        ? $app->db->one('SELECT id FROM ak_users WHERE phone = ? OR username = ?', [$phone, $username])
        : $app->db->one('SELECT id FROM ak_users WHERE phone = ?', [$phone]);
    if ($taken) {
        flash('error', 'این موبایل یا نام کاربری قبلاً ثبت شده. از ورود استفاده کنید.');
        redirect('/register');
    }
    $active = $kind === 'customer' && !register_flag($values, 'customer_needs_review') ? 1 : 0;
    $role = $kind === 'representative' ? 'representative' : 'customer';
    $dealersReady = $kind === 'representative' && register_dealers_ready($app);
    try {
        $created = $app->db->transaction(function (Database $db) use ($app, $name, $phone, $username, $password, $role, $active, $kind, $entity, $fields, $cooperation, $saleModel, $groups, $ip, $dealersReady): array {
            $db->run(
                'INSERT INTO ak_users (name, username, phone, password_hash, role, active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [
                    $name,
                    $username === '' ? null : $username,
                    $phone,
                    $password === '' ? null : password_hash($password, PASSWORD_DEFAULT),
                    $role,
                    $active,
                ]
            );
            $userId = (int) $db->id();
            $dealerId = null;
            if ($dealersReady) {
                $dealerId = register_insert_dealer($app, $db, $userId, $name, $phone, $entity, $fields, $cooperation, $saleModel, $groups, 'pending');
            }
            $db->run(
                'INSERT INTO ak_registrations (user_id, dealer_id, kind, entity, name, phone, username, province, city, address, national_id, shop_name, note, cooperation, sale_model, product_groups, status, ip, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $userId,
                    $dealerId,
                    $kind,
                    $entity,
                    $name,
                    $phone,
                    $username,
                    $fields['province'],
                    $fields['city'],
                    $fields['address'],
                    $fields['national_id'],
                    $fields['shop_name'],
                    $fields['note'],
                    $kind === 'representative' ? $cooperation : '',
                    $saleModel,
                    implode(',', $groups),
                    $active === 1 ? 'approved' : 'pending',
                    $ip,
                ]
            );
            return ['user_id' => $userId, 'active' => $active];
        });
    } catch (PDOException $e) {
        log_line($app->root, 'register ' . $e->getMessage());
        flash('error', (string) $e->getCode() === '23000' ? 'نام کاربری یا موبایل تکراری است.' : 'ثبت‌نام ذخیره نشد.');
        redirect('/register');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect('/register');
    }
    register_notify($app, $kind, $name);
    if ($kind === 'representative') {
        flash('success', $values['success_representative'] !== '' ? $values['success_representative'] : 'درخواست نمایندگی ثبت شد.');
        redirect('/register');
    }
    if ((int) $created['active'] !== 1) {
        flash('success', $values['success_review'] !== '' ? $values['success_review'] : 'درخواست ثبت شد.');
        redirect('/register');
    }
    $user = $app->db->one('SELECT * FROM ak_users WHERE id = ? AND active = 1', [(int) $created['user_id']]);
    if ($user) {
        $app->auth->login($user);
    }
    flash('success', $values['success_customer'] !== '' ? $values['success_customer'] : 'حساب ساخته شد.');
    redirect('/account');
}

/**
 * @param array<string, string> $values
 * @return array{province:string,city:string,address:string,national_id:string,shop_name:string,note:string}|null
 */
function register_collect_fields(array $values): ?array
{
    $maxes = [
        'province' => 80,
        'city' => 80,
        'address' => 240,
        'national_id' => 30,
        'shop_name' => 160,
        'note' => 1000,
    ];
    $out = [];
    foreach ($maxes as $key => $max) {
        $show = register_flag($values, 'show_' . ($key === 'shop_name' ? 'shop' : ($key === 'national_id' ? 'national_id' : $key)));
        $requireKey = 'require_' . ($key === 'shop_name' ? 'shop' : ($key === 'national_id' ? 'national_id' : $key));
        $value = $show ? post_string($key, $max) : '';
        if ($show && register_flag($values, $requireKey) && $value === '') {
            flash('error', (register_field_labels()[$key] ?? 'فیلد') . ' را پر کنید.');
            return null;
        }
        $out[$key] = $value;
    }
    return $out;
}

/**
 * @param array{province:string,city:string,address:string,national_id:string,shop_name:string,note:string} $fields
 * @param list<string> $groups
 */
function register_insert_dealer(App $app, Database $db, int $userId, string $name, string $phone, string $entity, array $fields, string $cooperation, string $saleModel, array $groups, string $status): int
{
    $code = '';
    for ($try = 0; $try < 6; $try++) {
        $candidate = 'AKN-' . random_int(100000, 999999);
        if (!$db->one('SELECT id FROM ak_dealers WHERE code = ?', [$candidate])) {
            $code = $candidate;
            break;
        }
    }
    if ($code === '') {
        throw new RuntimeException('کد نمایندگی ساخته نشد. دوباره بفرستید.');
    }
    $days = 30;
    if (function_exists('dealer_setting')) {
        $days = max(0, (int) dealer_setting($app, 'default_credit_days', '30'));
    }
    $db->run(
        'INSERT INTO ak_dealers (user_id, kind, name, national_id, phone, province, city, cooperation, sale_model, territory, product_groups, exclusive_city, exclusive_exception, level, credit_limit, credit_days, settlement, contract_start, contract_end, region_id, status, code, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, "", 0, ?, "", NULL, NULL, NULL, ?, ?, NOW())',
        [
            $userId,
            $entity === 'company' ? 'company' : 'person',
            $name,
            $fields['national_id'],
            $phone,
            $fields['province'],
            $fields['city'],
            $cooperation,
            $saleModel !== '' ? $saleModel : 'resale',
            $fields['city'],
            implode(',', $groups),
            $days,
            $status,
            $code,
        ]
    );
    return (int) $db->id();
}

function register_notify(App $app, string $kind, string $name): void
{
    $notify = setting($app, 'register', 'notify_phone', '');
    if ($notify === '') {
        $notify = setting($app, 'theme-settings', 'phone', '');
    }
    $notify = normalize_phone($notify);
    $sms = $app->get('sms');
    if (!is_mobile($notify) || !class_exists('NiazpardazClient') || !$sms instanceof NiazpardazClient || !$sms->configured()) {
        return;
    }
    $label = register_kind_labels()[$kind] ?? 'ثبت‌نام';
    try {
        $sms->send($notify, 'درخواست تازه آسان‌کشت: ' . $label . ' — ' . $name, 'register');
    } catch (Throwable $e) {
        log_line($app->root, 'register sms ' . $e->getMessage());
    }
}

function register_decide(App $app, array $actor): void
{
    $id = (int) post_string('id', 12);
    $decision = post_string('decision', 20);
    $row = $app->db->one('SELECT * FROM ak_registrations WHERE id = ?', [$id]);
    if (!$row || (string) $row['status'] !== 'pending' || ($decision !== 'approve' && $decision !== 'reject')) {
        flash('error', 'این درخواست قابل رسیدگی نیست.');
        redirect('/admin/register');
    }
    $userId = (int) ($row['user_id'] ?? 0);
    $user = $userId > 0 ? $app->db->one('SELECT * FROM ak_users WHERE id = ?', [$userId]) : null;
    if ($decision === 'approve') {
        register_approve($app, $row, $user, (int) $actor['id']);
        return;
    }
    register_reject($app, $row, $user, (int) $actor['id']);
}

/** @param array<string, mixed> $row */
function register_approve(App $app, array $row, ?array $user, int $actorId): void
{
    if (!$user) {
        flash('error', 'کاربر این درخواست پیدا نشد.');
        redirect('/admin/register');
    }
    $values = register_values($app);
    $dealerId = (int) ($row['dealer_id'] ?? 0);
    $dealersReady = register_dealers_ready($app);
    try {
        $app->db->transaction(function (Database $db) use ($app, $row, $user, $actorId, $values, &$dealerId, $dealersReady): void {
            $db->run('UPDATE ak_users SET active = 1 WHERE id = ?', [(int) $user['id']]);
            if ((string) $row['kind'] === 'representative') {
                if ($dealerId < 1 && $dealersReady) {
                    $fields = [
                        'province' => (string) $row['province'],
                        'city' => (string) $row['city'],
                        'address' => (string) $row['address'],
                        'national_id' => (string) $row['national_id'],
                        'shop_name' => (string) $row['shop_name'],
                        'note' => (string) $row['note'],
                    ];
                    $groups = array_values(array_filter(explode(',', (string) $row['product_groups'])));
                    $status = register_flag($values, 'rep_activate') ? 'active' : 'pending';
                    $sale = (string) $row['sale_model'] !== '' ? (string) $row['sale_model'] : 'resale';
                    $cooperation = (string) $row['cooperation'] !== '' ? (string) $row['cooperation'] : 'sale';
                    $dealerId = register_insert_dealer(
                        $app,
                        $db,
                        (int) $user['id'],
                        (string) $row['name'],
                        (string) $row['phone'],
                        (string) $row['entity'],
                        $fields,
                        $cooperation,
                        $sale,
                        $groups,
                        $status
                    );
                } elseif ($dealerId > 0 && register_flag($values, 'rep_activate')) {
                    $db->run('UPDATE ak_dealers SET status = "active" WHERE id = ? AND status = "pending"', [$dealerId]);
                }
            }
            $db->run(
                'UPDATE ak_registrations SET status = "approved", dealer_id = ?, decided_at = NOW(), decided_by = ? WHERE id = ?',
                [$dealerId > 0 ? $dealerId : null, $actorId, (int) $row['id']]
            );
        });
    } catch (Throwable $e) {
        log_line($app->root, 'register approve ' . $e->getMessage());
        flash('error', 'تأیید انجام نشد.');
        redirect('/admin/register');
    }
    $tail = '';
    if ((string) $row['kind'] === 'representative' && !register_flag($values, 'rep_activate')) {
        $tail = ' پرونده نمایندگی هنوز در انتظار است و سفارش تا فعال کردنش بسته می‌ماند.';
    }
    flash('success', 'درخواست تأیید شد و ورود باز شد.' . $tail);
    redirect('/admin/register');
}

/** @param array<string, mixed> $row */
function register_reject(App $app, array $row, ?array $user, int $actorId): void
{
    $userId = (int) ($row['user_id'] ?? 0);
    $dealerId = (int) ($row['dealer_id'] ?? 0);
    $orders = 0;
    $tickets = 0;
    if ($dealerId > 0 && register_dealers_ready($app)) {
        $found = $app->db->one('SELECT id FROM ak_dealer_orders WHERE dealer_id = ? LIMIT 1', [$dealerId]);
        $orders = $found ? 1 : 0;
    }
    if ($userId > 0) {
        try {
            $foundTicket = $app->db->one('SELECT id FROM ak_tickets WHERE user_id = ? LIMIT 1', [$userId]);
            $tickets = $foundTicket ? 1 : 0;
        } catch (Throwable) {
            $tickets = 0;
        }
    }
    try {
        $app->db->transaction(function (Database $db) use ($row, $user, $actorId, $dealerId, $orders, $tickets): void {
            if ($dealerId > 0 && $orders === 0) {
                $dealer = $db->one('SELECT status FROM ak_dealers WHERE id = ?', [$dealerId]);
                if ($dealer && (string) $dealer['status'] === 'pending') {
                    $db->run('DELETE FROM ak_dealers WHERE id = ?', [$dealerId]);
                }
            }
            if ($user && $tickets === 0 && (int) $user['active'] === 0) {
                $role = (string) $user['role'];
                if ($role === 'customer' || $role === 'representative') {
                    $db->run('DELETE FROM ak_users WHERE id = ?', [(int) $user['id']]);
                }
            } elseif ($user) {
                $db->run('UPDATE ak_users SET active = 0 WHERE id = ?', [(int) $user['id']]);
            }
            $db->run(
                'UPDATE ak_registrations SET status = "rejected", user_id = NULL, dealer_id = NULL, decided_at = NOW(), decided_by = ? WHERE id = ?',
                [$actorId, (int) $row['id']]
            );
        });
    } catch (Throwable $e) {
        log_line($app->root, 'register reject ' . $e->getMessage());
        flash('error', 'رد درخواست انجام نشد.');
        redirect('/admin/register');
    }
    flash('success', 'درخواست رد شد.');
    redirect('/admin/register');
}
