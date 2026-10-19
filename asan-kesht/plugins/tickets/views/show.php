<?php
/** @var array $ticket */
/** @var array $messages */
/** @var string $action */
/** @var bool $staff */
/** @var array<string, string> $statuses */
$staff = $staff ?? false;
$statuses = $statuses ?? [];
$roles = [
    'admin' => 'پشتیبانی',
    'employee' => 'پشتیبانی',
    'accountant' => 'حسابداری',
    'region_manager' => 'مدیر منطقه',
    'representative' => 'نماینده',
    'customer' => 'مشتری',
];
?>
<?php if (!$staff): ?><section class="section"><div class="wrap"><?php endif; ?>
  <p class="ticket-toolbar">
    <a class="ticket-back" href="<?= e(url($staff ? '/admin/tickets' : '/account/tickets')) ?>">تیکت‌ها</a>
  </p>
  <p class="ticket-head">
    <span class="ticket-pill st-<?= e((string) $ticket['status']) ?>"><?= e(ticket_status_label((string) $ticket['status'])) ?></span>
    <?php if ($staff && !empty($ticket['name'])): ?><span class="muted"><?= e((string) $ticket['name']) ?></span><?php endif; ?>
  </p>
  <div class="ticket-layout<?= $staff ? ' has-aside' : '' ?>">
    <div class="ticket-thread">
      <?php foreach ($messages as $message): ?>
        <?php
        $role = (string) ($message['role'] ?? '');
        $fromStaff = in_array($role, ['admin', 'employee', 'accountant', 'region_manager'], true);
        ?>
        <article class="ticket-msg <?= $fromStaff ? 'is-staff' : 'is-client' ?>">
          <strong><?= e((string) $message['name']) ?></strong>
          <span class="muted"><?= e(jalali_date((string) $message['created_at'])) ?> — <?= e($roles[$role] ?? $role) ?></span>
          <p><?= nl2br(e((string) $message['body'])) ?></p>
        </article>
      <?php endforeach; ?>
      <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="post" action="<?= e(url($action)) ?>" class="stack ticket-reply">
          <?= csrf_field() ?>
          <label>پاسخ <textarea name="body" required></textarea></label>
          <button class="btn" type="submit">ارسال پاسخ</button>
        </form>
      <?php else: ?>
        <p class="muted">این تیکت بسته است.</p>
      <?php endif; ?>
    </div>
    <?php if ($staff && $statuses !== []): ?>
      <aside class="ticket-status">
        <h2>وضعیت</h2>
        <form method="post" action="<?= e(url($action)) ?>">
          <?= csrf_field() ?>
          <?php foreach ($statuses as $key => $label): ?>
            <button type="submit" name="status" value="<?= e((string) $key) ?>" class="st-<?= e((string) $key) ?><?= $ticket['status'] === $key ? ' is-current' : '' ?>"><?= e((string) $label) ?></button>
          <?php endforeach; ?>
        </form>
      </aside>
    <?php endif; ?>
  </div>
<?php if (!$staff): ?></div></section><?php endif; ?>
