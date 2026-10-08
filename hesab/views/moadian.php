<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>تنظیمات سامانه مودیان</strong></div>
    <form method="post" action="<?= e(url('/moadian/save')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>کد اقتصادی<input name="economic_code" value="<?= e($settings['economic_code'] ?? '') ?>"></label>
      <label>شناسه حافظه مالیاتی<input name="memory_id" value="<?= e($settings['memory_id'] ?? '') ?>"></label>
      <label>کلید خصوصی (PEM)<textarea name="private_key" rows="6"><?= e($settings['private_key'] ?? '') ?></textarea></label>
      <label><input type="checkbox" name="is_enabled" <?= !empty($settings['is_enabled']) ? 'checked' : '' ?>> فعال‌سازی اتصال</label>
      <button class="btn" type="submit">ذخیره</button>
      <p style="color:var(--muted);font-size:12px">ارسال واقعی به tax.gov.ir پس از تکمیل کلید و گواهی فعال می‌شود؛ فعلاً صف ارسال آماده است.</p>
    </form>
  </div>
  <div class="panel">
    <div class="hd"><strong>صف ارسال صورتحساب</strong></div>
    <form method="post" action="<?= e(url('/moadian/queue-invoice')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>شناسه فاکتور داخلی<input name="invoice_id" type="number" required></label>
      <button class="btn" type="submit">افزودن به صف</button>
    </form>
    <div class="bd">
      <table class="data">
        <thead><tr><th>ID</th><th>فاکتور</th><th>وضعیت</th><th>ارسال</th></tr></thead>
        <tbody>
        <?php foreach ($queue as $q): ?>
          <tr>
            <td><?= (int)$q['id'] ?></td>
            <td><?= (int)($q['invoice_id'] ?? 0) ?></td>
            <td><?= e($q['status']) ?></td>
            <td class="num"><?= e($q['sent_at'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
