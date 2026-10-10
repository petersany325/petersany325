<div class="m-card">
  <div class="m-card-hd">
    <strong>اسناد حسابداری</strong>
    <a class="m-btn" href="<?= e(url('/m/vouchers/create')) ?>" style="min-height:34px;padding:0 10px;font-size:12px">جدید</a>
  </div>
  <div style="display:flex;gap:6px;padding:10px 12px;overflow:auto">
    <?php
    $statuses = ['' => 'همه', 'draft' => 'پیش‌نویس', 'operational' => 'عملیاتی', 'reviewed' => 'بررسی', 'locked' => 'قطعی'];
    foreach ($statuses as $k => $lab):
      $active = ($status ?? '') === $k;
    ?>
      <a class="m-chip <?= $active ? 'operational' : '' ?>" href="<?= e(url('/m/vouchers')) ?><?= $k !== '' ? '?status=' . urlencode($k) : '' ?>"><?= e($lab) ?></a>
    <?php endforeach; ?>
  </div>
  <ul class="m-list">
    <?php if (empty($rows)): ?>
      <li><div class="m-empty">موردی نیست</div></li>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <li>
        <a href="<?= e(url('/m/vouchers/view')) ?>?id=<?= (int)$r['id'] ?>">
          <div class="m-row-main">
            <div class="t1">#<?= (int)$r['number'] ?> · <?= e($r['description'] ?: '—') ?></div>
            <div class="t2"><?= e($r['voucher_date']) ?> · <?= e($r['type_title'] ?? '—') ?></div>
          </div>
          <span class="m-chip <?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
