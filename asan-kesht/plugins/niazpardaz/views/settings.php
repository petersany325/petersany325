<?php /** @var array $logs */ /** @var string $api_key */ /** @var string $username */ /** @var string $from_number */ /** @var bool $has_password */ ?>
<p class="muted">کلید API یا نام کاربری پنل <a href="https://niazpardaz-sms.com/webservice" target="_blank" rel="noopener">نیازپرداز</a> را اینجا بگذارید. رمز در صفحه نشان داده نمی‌شود. اگر کلید API پر باشد، وب‌سرویس نسخه ۲ استفاده می‌شود؛ وگرنه نشانی قدیمی پنل.</p>
<form method="post" action="<?= e(url('/admin/sms')) ?>" class="stack">
  <?= csrf_field() ?>
  <label>کلید API <input name="api_key" value="<?= e($api_key) ?>" autocomplete="off"></label>
  <label>نام کاربری <input name="username" value="<?= e($username) ?>"></label>
  <label>رمز پنل <?= $has_password ? '(ذخیره شده؛ برای تعویض بنویسید)' : '' ?> <input name="password" type="password" autocomplete="new-password"></label>
  <label>شماره خط فرستنده <input name="from_number" value="<?= e($from_number) ?>" placeholder="1000..."></label>
  <button type="submit">ذخیره اتصال</button>
</form>
<form method="post" action="<?= e(url('/admin/sms/test')) ?>" class="stack">
  <?= csrf_field() ?>
  <label>شماره تست <input name="test_phone" placeholder="09..." required></label>
  <button type="submit">ارسال یک پیامک تست</button>
</form>
<h2>آخرین ارسال‌ها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>زمان</th><th>شماره</th><th>موضوع</th><th>نتیجه</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= e(jalali_date((string) $log['created_at'])) ?></td>
        <td><?= e(fa_digits((string) $log['phone'])) ?></td>
        <td><?= e((string) $log['context']) ?></td>
        <td><?= (int) $log['ok'] === 1 ? 'ارسال شد' : e((string) $log['detail']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
