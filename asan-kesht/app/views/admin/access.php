<?php
/** @var array $sections */
/** @var array $matrix */
/** @var array $roles */
/** @var array $users */
$saved = $matrix !== [];
?>
<p class="muted">تیک هر نقش، همان بخش را برای آن نقش باز می‌گذارد. نقش‌هایی که این مسیر از اساس برایشان ساخته نشده با خط تیره آمده‌اند و از جدول نقش وارد نمی‌شوند. برای یک کاربر خاص، پایین صفحه «اجازه» حتی بیرون از نقش او را وارد همان بخش می‌کند و «ممنوع» همان بخش را از او می‌گیرد. مدیر همیشه دسترسی دارد.</p>
<p><a href="<?= e(url('/admin/users')) ?>">کاربران و نقش‌ها</a> · <a href="<?= e(url('/admin/accounting/audit')) ?>">حسابرسی</a></p>

<h2>دسترسی نقش‌ها</h2>
<form method="post" action="<?= e(url('/admin/access')) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>بخش</th>
          <?php foreach ($roles as $role => $label): ?>
            <th><?= e($label) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sections as $id => $section): ?>
          <tr>
            <td><?= e((string) $section['label']) ?></td>
            <?php foreach ($roles as $role => $label): ?>
              <?php
                $on = $role === 'admin'
                    || ($saved && in_array($role, $matrix[$id] ?? ['admin'], true))
                    || (!$saved && in_array($role, $section['roles'], true));
              ?>
              <td>
                <?php if ($role === 'admin'): ?>
                  <input type="checkbox" checked disabled>
                <?php elseif (!in_array($role, $section['roles'], true)): ?>
                  —
                <?php else: ?>
                  <input type="checkbox" name="sections[<?= e((string) $id) ?>][<?= e((string) $role) ?>]" <?= $on ? 'checked' : '' ?>>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <button type="submit">ذخیره دسترسی نقش‌ها</button>
</form>

<h2>استثنای هر کاربر</h2>
<p class="muted">پیش‌فرض یعنی همان جدول نقش. اجازه، این بخش را برای همین کاربر باز می‌کند. ممنوع، این بخش را از همین کاربر می‌بندد.</p>
<?php foreach ($users as $person): ?>
  <?php $flags = access_user_flags($app, (int) $person['id']); ?>
  <details class="access-user">
    <summary><?= e((string) $person['name']) ?> — <?= e($roles[(string) $person['role']] ?? (string) $person['role']) ?><?= (int) $person['active'] === 1 ? '' : ' (غیرفعال)' ?></summary>
    <form method="post" action="<?= e(url('/admin/access/user')) ?>" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?= e((string) $person['id']) ?>">
      <div class="table-wrap">
        <table>
          <thead><tr><th>بخش</th><th>وضعیت</th></tr></thead>
          <tbody>
            <?php foreach ($sections as $id => $section): ?>
              <?php $flag = $flags[$id] ?? ''; ?>
              <tr>
                <td><?= e((string) $section['label']) ?></td>
                <td>
                  <select name="flag[<?= e((string) $id) ?>]">
                    <option value="" <?= $flag === '' ? 'selected' : '' ?>>پیش‌فرض نقش</option>
                    <option value="allow" <?= $flag === 'allow' ? 'selected' : '' ?>>اجازه</option>
                    <option value="deny" <?= $flag === 'deny' ? 'selected' : '' ?>>ممنوع</option>
                  </select>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <button type="submit">ذخیره این کاربر</button>
    </form>
  </details>
<?php endforeach; ?>
<?php if ($users === []): ?>
  <p>کاربر غیرمدیر هنوز نیست.</p>
<?php endif; ?>
