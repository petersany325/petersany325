<?php

declare(strict_types=1);

/** @return array<string, string> */
function shop_online_methods(App $app): array
{
    $out = [];
    if (shop_flag($app, 'shop-pay-online', 'zarinpal_on', '0') && shop_online_zarinpal_merchant($app) !== '') {
        $out['zarinpal'] = 'زرین‌پال';
    }
    if (shop_flag($app, 'shop-pay-online', 'zibal_on', '0') && shop_online_zibal_merchant($app) !== '') {
        $out['zibal'] = 'زیبال';
    }
    if (shop_flag($app, 'shop-pay-online', 'saman_on', '0') && shop_online_saman_terminal($app) !== '') {
        $out['saman'] = 'درگاه سپ (بانک سامان)';
    }
    return $out;
}

function shop_online_zarinpal_merchant(App $app): string
{
    $merchant = setting($app, 'shop-pay-online', 'zarinpal_merchant', '');
    return preg_match('/^[0-9a-fA-F-]{32,40}$/', $merchant) === 1 ? $merchant : '';
}

function shop_online_zibal_merchant(App $app): string
{
    $merchant = setting($app, 'shop-pay-online', 'zibal_merchant', '');
    if (preg_match('/^[A-Za-z0-9_-]{3,40}$/', $merchant) === 1) {
        return $merchant;
    }
    if (shop_flag($app, 'shop-pay-online', 'zibal_sandbox', '0')) {
        return 'zibal';
    }
    return '';
}

function shop_online_saman_terminal(App $app): string
{
    $terminal = setting($app, 'shop-pay-online', 'saman_terminal', '');
    return preg_match('/^\d{4,12}$/', $terminal) === 1 ? $terminal : '';
}

function shop_online_begin(App $app, string $number): void
{
    if (!shop_ready($app, 'shop-pay-online')) {
        throw new ShopException('پرداخت آنلاین خاموش است.');
    }
    shop_extend_schema($app->db);
    $order = shop_online_order($app, $number);
    if ((string) $order['status'] === 'cancelled') {
        throw new ShopException('سفارش لغو شده است.');
    }
    if ((string) $order['payment_status'] === 'paid') {
        shop_online_finish($app, $order, true);
    }
    $method = (string) $order['payment_method'];
    if (!isset(shop_online_methods($app)[$method])) {
        throw new ShopException('این روش پرداخت الان در تنظیمات فروشگاه فعال نیست.');
    }
    if ((int) $order['total'] < 1000) {
        throw new ShopException('مبلغ سفارش برای درگاه کافی نیست.');
    }
    if ((string) $order['payment_method'] === 'saman' && (int) ($_SESSION['ak_shop_verify'] ?? 0) === (int) $order['id']) {
        shop_online_saman_verify($app, $order, (string) $order['payment_ref']);
    }
    match ($method) {
        'zarinpal' => shop_online_zarinpal_start($app, $order),
        'zibal' => shop_online_zibal_start($app, $order),
        'saman' => shop_online_saman_start($app, $order),
        default => throw new ShopException('درگاه شناخته نشد.'),
    };
}

function shop_online_callback(App $app, string $provider): void
{
    if (!in_array($provider, ['zarinpal', 'zibal', 'saman'], true)) {
        http_response_code(404);
        echo 'not found';
        return;
    }
    try {
        shop_extend_schema($app->db);
        match ($provider) {
            'zarinpal' => shop_online_zarinpal_back($app),
            'zibal' => shop_online_zibal_back($app),
            'saman' => shop_online_saman_back($app),
        };
    } catch (ShopException $e) {
        flash('error', $e->getMessage());
        redirect('/account/orders');
    } catch (Throwable $e) {
        log_line($app->root, 'shop pay ' . $e->getMessage());
        flash('error', 'تأیید پرداخت کامل نشد. اگر مبلغ کم شده، از خریدهای من دوباره تلاش کنید.');
        redirect('/account/orders');
    }
}

/** @param array<string, mixed> $order */
function shop_online_zarinpal_start(App $app, array $order): void
{
    $merchant = shop_online_zarinpal_merchant($app);
    if ($merchant === '') {
        throw new ShopException('شناسه مرچنت زرین‌پال ذخیره نشده است.');
    }
    $sandbox = shop_flag($app, 'shop-pay-online', 'zarinpal_sandbox', '0');
    $existing = (string) ($order['payment_ref'] ?? '');
    if (shop_online_reuse($order) && preg_match('/^[A-Za-z0-9]{10,80}$/', $existing) === 1) {
        $again = ($sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com') . '/pg/StartPay/' . $existing;
        shop_online_away($again);
    }
    $host = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://api.zarinpal.com';
    $payload = [
        'merchant_id' => $merchant,
        'amount' => (int) $order['total'],
        'callback_url' => shop_online_absolute('/pay/online/zarinpal'),
        'description' => 'سفارش ' . (string) $order['number'],
    ];
    $phone = (string) $order['phone'];
    if (preg_match('/^09\d{9}$/', $phone) === 1) {
        $payload['metadata'] = ['mobile' => $phone];
    }
    $data = shop_online_post($host . '/pg/v4/payment/request.json', $payload);
    $code = (int) ($data['data']['code'] ?? 0);
    $authority = (string) ($data['data']['authority'] ?? '');
    if ($code !== 100 || preg_match('/^[A-Za-z0-9]{10,80}$/', $authority) !== 1) {
        throw new ShopException('زرین‌پال درخواست پرداخت را نپذیرفت.');
    }
    shop_online_pending($app, $order, $authority);
    $start = ($sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com') . '/pg/StartPay/' . $authority;
    shop_online_away($start);
}

function shop_online_zarinpal_back(App $app): void
{
    $authority = shop_online_field('Authority');
    if (preg_match('/^[A-Za-z0-9]{10,80}$/', $authority) !== 1) {
        throw new ShopException('رسید زرین‌پال نامعتبر است.');
    }
    $order = shop_online_by_ref($app, 'zarinpal', $authority);
    if ((string) $order['payment_status'] === 'paid') {
        shop_online_finish($app, $order, true);
    }
    if (strtoupper(shop_online_field('Status')) !== 'OK') {
        shop_online_fail($app, $order, 'پرداخت در زرین‌پال انجام نشد.');
    }
    $merchant = shop_online_zarinpal_merchant($app);
    if ($merchant === '') {
        throw new ShopException('شناسه مرچنت زرین‌پال ذخیره نشده است.');
    }
    $sandbox = shop_flag($app, 'shop-pay-online', 'zarinpal_sandbox', '0');
    $host = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://api.zarinpal.com';
    $data = shop_online_post($host . '/pg/v4/payment/verify.json', [
        'merchant_id' => $merchant,
        'amount' => (int) $order['total'],
        'authority' => $authority,
    ]);
    $code = (int) ($data['data']['code'] ?? 0);
    if ($code !== 100 && $code !== 101) {
        shop_online_fail($app, $order, 'تأیید زرین‌پال ناموفق بود.');
    }
    $ref = (string) ($data['data']['ref_id'] ?? '');
    shop_online_mark_paid($app, $order, $ref !== '' ? $ref : $authority);
    shop_online_finish($app, shop_online_fresh($app, $order), true);
}

/** @param array<string, mixed> $order */
function shop_online_zibal_start(App $app, array $order): void
{
    $merchant = shop_online_zibal_merchant($app);
    if ($merchant === '') {
        throw new ShopException('مرچنت زیبال ذخیره نشده است.');
    }
    $existing = (string) ($order['payment_ref'] ?? '');
    if (shop_online_reuse($order) && preg_match('/^\d{1,20}$/', $existing) === 1) {
        shop_online_away('https://gateway.zibal.ir/start/' . $existing);
    }
    $data = shop_online_post('https://gateway.zibal.ir/v1/request', [
        'merchant' => $merchant,
        'amount' => (int) $order['total'],
        'callbackUrl' => shop_online_absolute('/pay/online/zibal'),
        'orderId' => (string) $order['number'],
        'description' => 'سفارش ' . (string) $order['number'],
        'mobile' => (string) $order['phone'],
    ]);
    $track = (string) ($data['trackId'] ?? '');
    if ((int) ($data['result'] ?? 0) !== 100 || preg_match('/^\d{1,20}$/', $track) !== 1) {
        throw new ShopException('زیبال درخواست پرداخت را نپذیرفت.');
    }
    shop_online_pending($app, $order, $track);
    shop_online_away('https://gateway.zibal.ir/start/' . $track);
}

function shop_online_zibal_back(App $app): void
{
    $track = shop_online_field('trackId');
    if (preg_match('/^\d{1,20}$/', $track) !== 1) {
        throw new ShopException('رسید زیبال نامعتبر است.');
    }
    $order = shop_online_by_ref($app, 'zibal', $track);
    if ((string) $order['payment_status'] === 'paid') {
        shop_online_finish($app, $order, true);
    }
    $postedNumber = shop_online_field('orderId');
    if ($postedNumber !== '' && $postedNumber !== (string) $order['number']) {
        throw new ShopException('شماره سفارش با رسید زیبال یکی نیست.');
    }
    if (shop_online_field('success') !== '1') {
        shop_online_fail($app, $order, 'پرداخت در زیبال انجام نشد.');
    }
    $merchant = shop_online_zibal_merchant($app);
    if ($merchant === '') {
        throw new ShopException('مرچنت زیبال ذخیره نشده است.');
    }
    $data = shop_online_post('https://gateway.zibal.ir/v1/verify', [
        'merchant' => $merchant,
        'trackId' => (int) $track,
    ]);
    $result = (int) ($data['result'] ?? 0);
    $amount = (int) ($data['amount'] ?? 0);
    if (($result !== 100 && $result !== 201) || $amount !== (int) $order['total']) {
        shop_online_fail($app, $order, 'تأیید زیبال با مبلغ سفارش یکی نشد.');
    }
    shop_online_mark_paid($app, $order, $track);
    shop_online_finish($app, shop_online_fresh($app, $order), true);
}

/** @param array<string, mixed> $order */
function shop_online_saman_start(App $app, array $order): void
{
    $terminal = shop_online_saman_terminal($app);
    if ($terminal === '') {
        throw new ShopException('شماره پایانه سامان ذخیره نشده است.');
    }
    $payload = [
        'action' => 'token',
        'TerminalId' => $terminal,
        'Amount' => (int) $order['total'],
        'ResNum' => (string) $order['number'],
        'RedirectUrl' => shop_online_absolute('/pay/online/saman'),
    ];
    $phone = (string) $order['phone'];
    if (preg_match('/^09\d{9}$/', $phone) === 1) {
        $payload['CellNumber'] = $phone;
    }
    $data = shop_online_post('https://sep.shaparak.ir/onlinepg/onlinepg', $payload);
    $token = (string) ($data['token'] ?? '');
    if ((int) ($data['status'] ?? 0) !== 1 || preg_match('/^[A-Za-z0-9+\/=_-]{8,200}$/', $token) !== 1) {
        throw new ShopException('درگاه سپ درخواست پرداخت را نپذیرفت.');
    }
    shop_online_pending($app, $order, substr($token, 0, 80));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>انتقال به بانک</title></head><body>';
    echo '<form id="sep" method="post" action="https://sep.shaparak.ir/OnlinePG/OnlinePG">';
    echo '<input type="hidden" name="Token" value="' . e($token) . '">';
    echo '<p>در حال انتقال به درگاه سپ…</p><button type="submit">ادامه پرداخت</button></form>';
    echo '<script>document.getElementById("sep").submit();</script></body></html>';
    exit;
}

function shop_online_saman_back(App $app): void
{
    $number = shop_online_field('ResNum');
    $order = shop_online_order($app, $number);
    if ((string) $order['payment_method'] !== 'saman') {
        throw new ShopException('این سفارش پرداخت سپ نیست.');
    }
    if ((string) $order['payment_status'] === 'paid') {
        shop_online_finish($app, $order, true);
    }
    $ref = shop_online_field('RefNum');
    $status = shop_online_field('Status');
    $postedAmount = (int) shop_online_field('Amount');
    if ($status !== '2' || $ref === '' || strlen($ref) > 80 || $postedAmount !== (int) $order['total']) {
        shop_online_fail($app, $order, 'پرداخت در درگاه سپ انجام نشد.');
    }
    shop_online_pending($app, $order, $ref);
    $_SESSION['ak_shop_verify'] = (int) $order['id'];
    shop_online_saman_verify($app, $order, $ref);
}

/** @param array<string, mixed> $order */
function shop_online_saman_verify(App $app, array $order, string $ref): void
{
    if ($ref === '' || strlen($ref) > 80) {
        throw new ShopException('رسید درگاه سپ برای تأیید کافی نیست.');
    }
    $terminal = shop_online_saman_terminal($app);
    if ($terminal === '') {
        throw new ShopException('شماره پایانه سامان ذخیره نشده است.');
    }
    $data = shop_online_post('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', [
        'RefNum' => $ref,
        'TerminalNumber' => (int) $terminal,
    ]);
    $result = (int) ($data['ResultCode'] ?? -1);
    $detail = $data['TransactionDetail'] ?? null;
    $detail = is_array($detail) ? $detail : [];
    $amount = (int) ($detail['OriginalAmount'] ?? $detail['OrginalAmount'] ?? 0);
    $seenTerminal = (string) ($detail['TerminalNumber'] ?? '');
    $accepted = $result === 0 || $result === 2;
    if (!$accepted || $amount !== (int) $order['total'] || ($seenTerminal !== '' && $seenTerminal !== $terminal)) {
        unset($_SESSION['ak_shop_verify']);
        shop_online_fail($app, $order, 'تأیید درگاه سپ با مبلغ سفارش یکی نشد.');
    }
    unset($_SESSION['ak_shop_verify']);
    shop_online_mark_paid($app, $order, $ref);
    shop_online_finish($app, shop_online_fresh($app, $order), true);
}

/** @param array<string, mixed> $order */
function shop_online_reuse(array $order): bool
{
    if ((string) ($order['payment_status'] ?? '') !== 'pending') {
        return false;
    }
    $created = strtotime((string) ($order['created_at'] ?? ''));
    return $created !== false && (time() - $created) < 1800;
}

/** @return array<string, mixed> */
function shop_online_order(App $app, string $number): array
{
    if (preg_match('/^AKS-\d{6}-\d{3}$/', $number) !== 1) {
        throw new ShopException('شماره سفارش نامعتبر است.');
    }
    $order = $app->db->one('SELECT * FROM ak_shop_orders WHERE number = ?', [$number]);
    if (!$order) {
        throw new ShopException('سفارش پیدا نشد.');
    }
    return $order;
}

/** @return array<string, mixed> */
function shop_online_by_ref(App $app, string $method, string $ref): array
{
    $order = $app->db->one(
        'SELECT * FROM ak_shop_orders WHERE payment_method = ? AND payment_ref = ? ORDER BY id DESC LIMIT 1',
        [$method, $ref]
    );
    if (!$order) {
        throw new ShopException('سفارش این پرداخت پیدا نشد.');
    }
    return $order;
}

/** @param array<string, mixed> $order */
function shop_online_fresh(App $app, array $order): array
{
    $fresh = $app->db->one('SELECT * FROM ak_shop_orders WHERE id = ?', [(int) $order['id']]);
    return $fresh ?: $order;
}

/** @param array<string, mixed> $order */
function shop_online_pending(App $app, array $order, string $ref): void
{
    $app->db->run(
        'UPDATE ak_shop_orders SET payment_status = "pending", payment_ref = ? WHERE id = ? AND payment_status != "paid"',
        [substr($ref, 0, 80), (int) $order['id']]
    );
}

/** @param array<string, mixed> $order */
function shop_online_mark_paid(App $app, array $order, string $ref): void
{
    if ((string) $order['status'] === 'cancelled') {
        throw new ShopException('سفارش لغو شده و پرداخت در فروش ثبت نشد.');
    }
    $app->db->run(
        'UPDATE ak_shop_orders SET payment_status = "paid", payment_ref = ?, status = "confirmed" WHERE id = ? AND status != "cancelled" AND payment_status != "paid"',
        [substr($ref, 0, 80), (int) $order['id']]
    );
    if (!function_exists('accounting_sync_shop_order')) {
        return;
    }
    try {
        accounting_sync_shop_order($app, (int) $order['id']);
    } catch (Throwable $e) {
        log_line($app->root, 'shop pay sync ' . $e->getMessage());
    }
}

/** @param array<string, mixed> $order */
function shop_online_fail(App $app, array $order, string $message): void
{
    $app->db->run(
        'UPDATE ak_shop_orders SET payment_status = "failed" WHERE id = ? AND payment_status = "pending"',
        [(int) $order['id']]
    );
    flash('error', $message);
    shop_online_finish($app, $order, false);
}

/** @param array<string, mixed> $order */
function shop_online_finish(App $app, array $order, bool $paid): void
{
    $_SESSION['ak_shop_thanks'] = [
        'number' => (string) $order['number'],
        'total' => (int) $order['total'],
        'payment' => (string) $order['payment_method'],
        'paid' => $paid,
        'id' => (int) $order['id'],
    ];
    redirect('/checkout/thanks');
}

function shop_online_field(string $key): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    if (strlen($value) > 200) {
        return '';
    }
    return $value;
}

function shop_online_absolute(string $path): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (preg_match('/^[A-Za-z0-9.-]+(?::\d{1,5})?$/', $host) !== 1) {
        throw new ShopException('نشانی بازگشت درگاه ساخته نشد.');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    return ($https ? 'https://' : 'http://') . $host . url($path);
}

function shop_online_away(string $url): void
{
    $ok = preg_match('#^https://www\.zarinpal\.com/pg/StartPay/[A-Za-z0-9]+$#', $url) === 1
        || preg_match('#^https://sandbox\.zarinpal\.com/pg/StartPay/[A-Za-z0-9]+$#', $url) === 1
        || preg_match('#^https://gateway\.zibal\.ir/start/\d+$#', $url) === 1;
    if (!$ok) {
        throw new ShopException('نشانی درگاه نامعتبر است.');
    }
    header('Location: ' . $url);
    exit;
}

/** @param array<string, mixed> $payload */
function shop_online_post(string $url, array $payload): array
{
    $allowed = [
        'https://api.zarinpal.com/pg/v4/payment/request.json' => true,
        'https://api.zarinpal.com/pg/v4/payment/verify.json' => true,
        'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => true,
        'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' => true,
        'https://gateway.zibal.ir/v1/request' => true,
        'https://gateway.zibal.ir/v1/verify' => true,
        'https://sep.shaparak.ir/onlinepg/onlinepg' => true,
        'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => true,
    ];
    if (!isset($allowed[$url])) {
        throw new ShopException('نشانی سرویس درگاه مجاز نیست.');
    }
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new ShopException('درخواست درگاه ساخته نشد.');
    }
    $raw = shop_online_http($url, $body);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new ShopException('پاسخ درگاه خوانده نشد.');
    }
    return $decoded;
}

function shop_online_http(string $url, string $body): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new ShopException('درگاه پاسخ نداد.');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($raw) || $raw === '' || $code < 200 || $code >= 500) {
            throw new ShopException('درگاه پاسخ نداد.');
        }
        return $raw;
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $body,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);
    $raw = file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        throw new ShopException('درگاه پاسخ نداد.');
    }
    return $raw;
}
