<?php /** @var array $users */ ?>
<form method="post" action="<?= e(url('/admin/users')) ?>" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="0">
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
<div class="table-wrap">
  <table>
    <thead><tr><th>نام</th><th>نقش</th><th>موبایل</th><th>کاربری</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
      <tr>
        <td colspan="6">
          <form method="post" action="<?= e(url('/admin/users')) ?>" class="grid">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">
            <label>نام <input name="name" value="<?= e((string) $user['name']) ?>"></label>
            <label>کاربری <input name="username" value="<?= e((string) ($user['username'] ?? '')) ?>"></label>
            <label>موبایل <input name="phone" value="<?= e((string) ($user['phone'] ?? '')) ?>"></label>
            <label>نقش
              <select name="role">
                <?php foreach (ak_role_labels() as $key => $label): ?>
                  <option value="<?= e($key) ?>" <?= $user['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>رمز جدید <input name="password" type="password" autocomplete="new-password"></label>
            <label class="check"><input type="checkbox" name="active" <?= (int) $user['active'] === 1 ? 'checked' : '' ?>> فعال</label>
            <button type="submit">ذخیره</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
