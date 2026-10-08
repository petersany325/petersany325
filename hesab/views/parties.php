<div class="grid" style="grid-template-columns:320px 1fr;gap:12px">
  <form class="panel form" method="post" action="/parties">
    <?= csrf_field() ?>
    <div class="hd" style="margin:-14px -14px 0;border:0;border-bottom:1px solid var(--line)"><strong>طرف‌حساب جدید</strong></div>
    <label>کد<input name="code" required placeholder="C-001"></label>
    <label>نام<input name="name" required></label>
    <label>نوع
      <select name="type">
        <option value="customer">مشتری</option>
        <option value="supplier">تأمین‌کننده</option>
        <option value="both">هر دو</option>
        <option value="other">سایر</option>
      </select>
    </label>
    <label>تلفن<input name="phone"></label>
    <button class="btn" type="submit">ذخیره</button>
  </form>

  <div class="panel">
    <div class="hd"><strong>لیست طرف‌حساب‌ها</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>کد</th><th>نام</th><th>نوع</th><th>تلفن</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="4">موردی نیست</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['code']) ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['type']) ?></td>
            <td class="num"><?= e($r['phone'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
