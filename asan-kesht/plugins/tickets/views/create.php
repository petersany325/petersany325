<?php
/** @var bool $staff */
/** @var list<array<string, mixed>> $clients */
/** @var array<string, string> $statuses */
$staff = $staff ?? false;
$clients = $clients ?? [];
$statuses = $statuses ?? [];
$action = $staff ? '/admin/tickets/new' : '/account/tickets/new';
$back = $staff ? '/admin/tickets' : '/account/tickets';
$roles = ak_role_labels();
?>
<?php if (!$staff): ?><section class="section"><div class="wrap"><?php endif; ?>
  <div class="ticket-toolbar">
    <a class="ticket-back" href="<?= e(url($back)) ?>">بازگشت به تیکت‌ها</a>
  </div>
  <form method="post" action="<?= e(url($action)) ?>" class="stack ticket-form">
    <?= csrf_field() ?>
    <?php if ($staff): ?>
      <label>مشتری
        <select name="user_id" required>
          <option value="">انتخاب کنید</option>
          <?php foreach ($clients as $client): ?>
            <?php $role = (string) ($client['role'] ?? ''); ?>
            <option value="<?= e((string) $client['id']) ?>"><?= e((string) $client['name']) ?> — <?= e($roles[$role] ?? $role) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($clients === []): ?>
        <p class="muted">مشتری یا نمایندهٔ فعالی برای ثبت تیکت نیست.</p>
      <?php endif; ?>
      <label>وضعیت اولیه
        <select name="status">
          <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= e((string) $key) ?>"<?= $key === 'open' ? ' selected' : '' ?>><?= e((string) $label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <label>موضوع <input name="subject" required maxlength="180"></label>
    <label>شرح <textarea name="body" required></textarea></label>
    <button class="btn" type="submit"<?= $staff && $clients === [] ? ' disabled' : '' ?>>ایجاد تیکت</button>
  </form>
<?php if (!$staff): ?></div></section><?php endif; ?>
