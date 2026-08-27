<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php#contact');
}

$token = $_POST['csrf_token'] ?? '';
if (!csrf_verify($token)) {
    flash('error', 'نشست منقضی شده است. لطفاً دوباره تلاش کنید.');
    redirect('index.php#contact');
}

$name = trim((string) ($_POST['name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$topic = trim((string) ($_POST['topic'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$honeypot = trim((string) ($_POST['website'] ?? ''));

store_old([
    'name' => $name,
    'phone' => $phone,
    'topic' => $topic,
    'message' => $message,
]);

if ($honeypot !== '') {
    // ربات احتمالی — بدون خطا رد شو
    clear_old();
    flash('success', 'درخواست شما با موفقیت ثبت شد.');
    redirect('index.php#contact');
}

$allowedTopics = ['حقوق خانواده', 'کیفری', 'قرارداد و تجارت', 'ملک و ثبت', 'سایر'];

$errors = [];
if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = 'نام را کامل وارد کنید.';
}
if ($phone === '' || mb_strlen($phone) < 8) {
    $errors[] = 'شماره تماس معتبر وارد کنید.';
}
if (!in_array($topic, $allowedTopics, true)) {
    $errors[] = 'موضوع پرونده را انتخاب کنید.';
}
if (mb_strlen($message) > 2000) {
    $errors[] = 'توضیح کوتاه‌تر بنویسید.';
}

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('index.php#contact');
}

$lead = [
    'id' => date('Ymd-His') . '-' . bin2hex(random_bytes(3)),
    'created_at' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'name' => $name,
    'phone' => $phone,
    'topic' => $topic,
    'message' => $message,
];

$saved = false;
if (!empty($config['save_leads'])) {
    $dir = __DIR__ . '/storage/leads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . $lead['id'] . '.json';
    $saved = (bool) file_put_contents(
        $file,
        json_encode($lead, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

$mailed = false;
if (!empty($config['send_mail'])) {
    $to = (string) $config['email_to'];
    $from = (string) $config['email_from'];
    $subject = '=?UTF-8?B?' . base64_encode('درخواست مشاوره جدید — ' . $topic) . '?=';
    $body = "نام: {$name}\nتلفن: {$phone}\nموضوع: {$topic}\n\nپیام:\n{$message}\n\nIP: {$lead['ip']}\nزمان: {$lead['created_at']}\n";
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from,
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    $mailed = @mail($to, $subject, $body, implode("\r\n", $headers));
}

if ($saved || $mailed || empty($config['save_leads'])) {
    clear_old();
    flash('success', 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.');
} else {
    flash('error', 'ثبت درخواست ناموفق بود. لطفاً تلفنی تماس بگیرید.');
}

redirect('index.php#contact');
