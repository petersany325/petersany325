<?php /** @var array $ticket */ /** @var array $messages */ /** @var string $action */ /** @var bool $staff */ $staff = $staff ?? false; ?>
<?php if (!$staff): ?><section class="section"><div class="wrap"><?php endif; ?>
  <p class="muted">وضعیت: <?= e((string) $ticket['status']) ?></p>
  <?php foreach ($messages as $message): ?>
    <article class="stack">
      <strong><?= e((string) $message['name']) ?></strong>
      <span class="muted"><?= e(jalali_date((string) $message['created_at'])) ?> — <?= e((string) $message['role']) ?></span>
      <p><?= nl2br(e((string) $message['body'])) ?></p>
    </article>
  <?php endforeach; ?>
  <?php if ($ticket['status'] !== 'closed'): ?>
    <form method="post" action="<?= e(url($action)) ?>" class="stack">
      <?= csrf_field() ?>
      <label>پاسخ <textarea name="body" required></textarea></label>
      <button type="submit">ارسال پاسخ</button>
      <?php if ($staff): ?><button type="submit" name="close" value="1">بستن تیکت</button><?php endif; ?>
    </form>
  <?php endif; ?>
<?php if (!$staff): ?></div></section><?php endif; ?>
