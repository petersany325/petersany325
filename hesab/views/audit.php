<div class="panel">
  <div class="hd">
    <strong>تاریخچه فعالیت کاربران</strong>
    <a class="btn ghost" href="?excel=1">Excel</a>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr><th>زمان</th><th>کاربر</th><th>عمل</th><th>موجودیت</th><th>شناسه</th><th>شرح</th><th>IP</th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7">موردی نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="num"><?= e($r['created_at']) ?></td>
          <td><?= e($r['user_name'] ?? '—') ?></td>
          <td><?= e($r['action']) ?></td>
          <td><?= e($r['entity'] ?? '—') ?></td>
          <td class="num"><?= e((string)($r['entity_id'] ?? '')) ?></td>
          <td><?= e($r['detail'] ?? '') ?></td>
          <td class="num"><?= e($r['ip'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
