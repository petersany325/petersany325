<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>فیلتر وضعیت</strong>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php
      $statuses = ['' => 'همه', 'draft' => 'پیش‌نویس', 'operational' => 'عملیاتی', 'reviewed' => 'بررسی‌شده', 'locked' => 'قطعی'];
      foreach ($statuses as $k => $lab):
      ?>
        <a class="btn ghost <?= ($status ?? '') === $k ? 'active' : '' ?>" href="<?= e(url('/vouchers')) ?><?= $k !== '' ? '?status=' . urlencode($k) : '' ?>"><?= e($lab) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <strong>فهرست اسناد</strong>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if (Permission::can(current_user(), 'vouchers.renumber')): ?>
      <form method="post" action="<?= e(url('/vouchers/renumber')) ?>" style="margin:0" onsubmit="return confirm('مرتب‌سازی شماره اسناد؟')">
        <?= csrf_field() ?>
        <button class="btn ghost" type="submit">مرتب‌سازی شماره</button>
      </form>
      <form method="post" action="<?= e(url('/vouchers/gap')) ?>" class="inline-form" style="display:flex;gap:6px;align-items:center;margin:0">
        <?= csrf_field() ?>
        <input name="after_number" placeholder="بعد از شماره" style="width:100px" required>
        <input name="gap_count" value="1" style="width:60px" title="تعداد فاصله">
        <button class="btn ghost" type="submit">ایجاد فاصله</button>
      </form>
      <?php endif; ?>
      <a class="btn" href="<?= e(url('/vouchers/create')) ?>">سند جدید</a>
    </div>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>شماره</th>
          <th>تاریخ</th>
          <th>نوع</th>
          <th>شرح</th>
          <th>وضعیت</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="6">موردی نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td><?= e($r['type_title'] ?? '—') ?></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td><span class="badge <?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
          <td><a class="btn ghost" href="<?= e(url('/vouchers/view')) ?>?id=<?= (int)$r['id'] ?>">مشاهده</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
