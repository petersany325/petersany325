<?php /** @var array $users */ /** @var int $actor_id */ ?>
<nav class="acct-nav">
  <a class="is-current" href="<?= e(url('/admin/users')) ?>">کاربران</a>
  <a href="<?= e(url('/admin/access')) ?>">دسترسی‌ها</a>
</nav>
<p class="muted">برای هر کاربر ویرایش و حذف جداست. حساب خودتان و آخرین مدیر حذف نمی‌شود. اگر نماینده، تیکت یا سفارش داشته باشد، حذف نمی‌شود و از ویرایش غیرفعال می‌شود.</p>
<h2>کاربر تازه</h2>
<form method="post" action="<?= e(url('/admin/users')) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="grid">
    <label>نام <input name="name" required></label>
    <label>نام کاربری <input name="username" placeholder="برای ورود با رمز"></label>
    <label>موبایل <input name="phone" placeholder="09..."></label>
    <label>نقش
      <select name="role">
        <?php foreach (ak_role_labels() as $key => $label): ?>
          <option value="<?= e($key) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>رمز اختیاری <input name="password" type="password" autocomplete="new-password"></label>
    <label class="check"><input type="checkbox" name="active" checked> فعال</label>
  </div>
  <button type="submit">افزودن کاربر</button>
</form>
<h2>فهرست</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>نام</th><th>نقش</th><th>موبایل</th><th>کاربری</th><th>وضعیت</th><th>ویرایش و حذف</th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= e((string) $user['name']) ?></td>
        <td><?= e(ak_role_labels()[(string) $user['role']] ?? (string) $user['role']) ?></td>
        <td><?= e((string) ($user['phone'] ?? '')) ?></td>
        <td><?= e((string) ($user['username'] ?? '')) ?></td>
        <td><?= (int) $user['active'] === 1 ? 'فعال' : 'غیرفعال' ?></td>
        <td>
          <div class="user-actions">
            <a class="btn" href="<?= e(url('/admin/users/' . $user['id'])) ?>">ویرایش</a>
            <?php if ((int) $user['id'] !== $actor_id): ?>
              <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/delete')) ?>">
                <?= csrf_field() ?>
                <label class="check"><input type="checkbox" name="confirm" value="1"> حذف شود</label>
                <button type="submit">حذف</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($users === []): ?>
      <tr><td colspan="6">کاربری ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
