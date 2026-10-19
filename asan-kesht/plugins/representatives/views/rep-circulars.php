<?php /** @var array $rows */ ?>
<?php require __DIR__ . '/rep-nav.php'; ?>
<?php foreach ($rows as $row): ?>
  <article>
    <h2><?= e((string) $row['title']) ?></h2>
    <p class="muted"><?= e($row['kind'] === 'law' ? 'قانون' : 'بخشنامه') ?> — <?= e((string) $row['priority']) ?> — <?= e((string) $row['receipt_status']) ?></p>
    <p><?= nl2br(e((string) $row['body'])) ?></p>
    <?php if ($row['report'] !== ''): ?><p>گزارش شما: <?= e((string) $row['report']) ?></p><?php endif; ?>
    <?php
      $next = ['seen' => 'acked', 'acked' => 'doing', 'doing' => 'done', 'sent' => 'acked'][$row['receipt_status']] ?? '';
      $labels = ['acked' => 'دریافت را تأیید می‌کنم', 'doing' => 'در حال اجراست', 'done' => 'انجام شد'];
    ?>
    <?php if ($next !== ''): ?>
      <form method="post" action="<?= e(url('/rep/circulars/' . $row['id'])) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="<?= e($next) ?>">
        <?php if ($next === 'done'): ?><label>گزارش انجام <textarea name="report" required></textarea></label><?php endif; ?>
        <button type="submit"><?= e($labels[$next]) ?></button>
      </form>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
<?php if ($rows === []): ?><p>بخشنامه‌ای برای شما نیست.</p><?php endif; ?>
