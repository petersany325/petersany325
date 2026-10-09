<?php
/** @var array $rows */
/** @var array $visitors */
/** @var string $status */
/** @var int $openCount */
$statusLabels = [
    'open' => 'باز',
    'in_progress' => 'در جریان',
    'done' => 'انجام‌شده',
    'rejected' => 'رد شده',
];
$kindLabels = [
    'task' => 'وظیفه',
    'visit' => 'بازدید',
    'commission' => 'پورسانت',
    'sms' => 'پیامک',
    'other' => 'سایر',
];
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>کارتابل ویزیتور</strong>
    <span style="color:var(--muted);font-size:12px">باز / جاری: <?= (int)$openCount ?></span>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach (['open'=>'باز','in_progress'=>'جاری','done'=>'انجام','rejected'=>'رد','all'=>'همه'] as $k=>$lab): ?>
        <a class="btn <?= $status===$k?'':'ghost' ?>" href="<?= e(url('/visitors/cartable?status=' . $k)) ?>"><?= e($lab) ?></a>
      <?php endforeach; ?>
      <a class="btn ghost" href="<?= e(url('/visitors')) ?>">تعاریف</a>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns:360px 1fr;gap:12px">
  <form class="panel form" method="post" action="<?= e(url('/visitors/cartable')) ?>" style="padding:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="op" value="create">
    <div class="hd" style="margin:-12px -12px 12px;padding:10px 12px"><strong>ثبت در کارتابل</strong></div>
    <label>ویزیتور
      <select name="visitor_id">
        <option value="0">— عمومی —</option>
        <?php foreach ($visitors as $v): ?>
          <option value="<?= (int)$v['id'] ?>"><?= e($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>نوع
      <select name="kind">
        <?php foreach ($kindLabels as $k=>$lab): ?>
          <option value="<?= e($k) ?>"><?= e($lab) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>عنوان<input name="title" required></label>
    <label>شرح<textarea name="body" rows="3"></textarea></label>
    <label>سررسید<input type="date" name="due_date"></label>
    <label><input type="checkbox" name="send_sms" checked> ارسال SMS به ویزیتور (نیازپرداز)</label>
    <button class="btn" type="submit">افزودن به کارتابل</button>
  </form>

  <div class="panel">
    <div class="hd"><strong>اقلام کارتابل</strong></div>
    <div class="bd">
      <table class="data">
        <thead>
          <tr><th>#</th><th>ویزیتور</th><th>نوع</th><th>عنوان</th><th>سررسید</th><th>وضعیت</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7">موردی نیست</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['visitor_name'] ?: 'عمومی') ?></td>
            <td><?= e($kindLabels[$r['kind']] ?? $r['kind']) ?></td>
            <td>
              <strong><?= e($r['title']) ?></strong>
              <?php if (!empty($r['body'])): ?>
                <div style="color:var(--muted);font-size:11.5px;margin-top:3px"><?= e($r['body']) ?></div>
              <?php endif; ?>
            </td>
            <td class="num"><?= e($r['due_date'] ?: '—') ?></td>
            <td><?= e($statusLabels[$r['status']] ?? $r['status']) ?></td>
            <td>
              <?php if (in_array($r['status'], ['open','in_progress'], true)): ?>
                <form method="post" action="<?= e(url('/visitors/cartable')) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="op" value="status">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="back" value="<?= e($status) ?>">
                  <button class="btn ghost" name="status" value="in_progress" type="submit">جاری</button>
                  <button class="btn" name="status" value="done" type="submit">انجام</button>
                  <button class="btn ghost" name="status" value="rejected" type="submit">رد</button>
                </form>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
