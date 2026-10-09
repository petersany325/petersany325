<?php
/** @var array $rows */
/** @var array $visitors */
/** @var array $parties */
/** @var int $visitorFilter */
$statusLabels = [
    'planned' => 'برنامه‌ریزی',
    'done' => 'انجام‌شده',
    'cancelled' => 'لغو',
    'no_sale' => 'بدون فروش',
];
?>
<div class="grid" style="grid-template-columns:360px 1fr;gap:12px">
  <form class="panel form" method="post" action="<?= e(url('/visitors/visits')) ?>" style="padding:12px">
    <?= csrf_field() ?>
    <div class="hd" style="margin:-12px -12px 12px;padding:10px 12px"><strong>ثبت بازدید</strong></div>
    <label>ویزیتور
      <select name="visitor_id" required>
        <option value="">— انتخاب —</option>
        <?php foreach ($visitors as $v): ?>
          <option value="<?= (int)$v['id'] ?>" <?= $visitorFilter===(int)$v['id']?'selected':'' ?>><?= e($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>مشتری
      <select name="party_id">
        <option value="0">— اختیاری —</option>
        <?php foreach ($parties as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تاریخ<input type="date" name="visit_date" value="<?= e(date('Y-m-d')) ?>" required></label>
    <label>ساعت<input name="visit_time" dir="ltr" placeholder="10:30"></label>
    <label>وضعیت
      <select name="status">
        <?php foreach ($statusLabels as $k=>$lab): ?>
          <option value="<?= e($k) ?>"><?= e($lab) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>نتیجه / شرح<textarea name="result_note" rows="2"></textarea></label>
    <label>پیگیری بعدی<input type="date" name="next_followup"></label>
    <label><input type="checkbox" name="send_sms"> اطلاع SMS به ویزیتور</label>
    <button class="btn" type="submit">ثبت بازدید + کارتابل</button>
  </form>

  <div class="panel">
    <div class="hd">
      <strong>گزارش بازدیدها</strong>
      <form method="get" action="<?= e(url('/visitors/visits')) ?>" style="display:flex;gap:6px;align-items:center">
        <select name="visitor_id" onchange="this.form.submit()">
          <option value="0">همه ویزیتورها</option>
          <?php foreach ($visitors as $v): ?>
            <option value="<?= (int)$v['id'] ?>" <?= $visitorFilter===(int)$v['id']?'selected':'' ?>><?= e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <a class="btn ghost" href="<?= e(url('/visitors/cartable')) ?>">کارتابل</a>
      </form>
    </div>
    <div class="bd">
      <table class="data">
        <thead>
          <tr><th>تاریخ</th><th>ویزیتور</th><th>مشتری</th><th>وضعیت</th><th>شرح</th><th>پیگیری</th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6">بازدیدی ثبت نشده</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="num"><?= e($r['visit_date']) ?><?= !empty($r['visit_time']) ? ' ' . e($r['visit_time']) : '' ?></td>
            <td><?= e($r['visitor_name']) ?></td>
            <td><?= e($r['party_name'] ?: '—') ?></td>
            <td><?= e($statusLabels[$r['status']] ?? $r['status']) ?></td>
            <td><?= e($r['result_note'] ?: '—') ?></td>
            <td class="num"><?= e($r['next_followup'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
