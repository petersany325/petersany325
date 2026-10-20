<?php /** @var array $person */ ?>
<nav class="acct-nav">
  <a href="<?= e(url('/admin/users')) ?>">کاربران</a>
  <a class="is-current" href="<?= e(url('/admin/users/' . $person['id'])) ?>">ویرایش</a>
  <a href="<?= e(url('/admin/access')) ?>">دسترسی‌ها</a>
</nav>
<p class="muted">رمز را خالی بگذارید تا رمز قبلی بماند. غیرفعال کردن، ورود را می‌بندد و پرونده‌ها را پاک نمی‌کند.</p>
<form method="post" action="<?= e(url('/admin/users/' . $person['id'])) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="grid">
    <label>نام <input name="name" required value="<?= e((string) $person['name']) ?>"></label>
    <label>نام کاربری <input name="username" value="<?= e((string) ($person['username'] ?? '')) ?>"></label>
    <label>موبایل <input name="phone" value="<?= e((string) ($person['phone'] ?? '')) ?>" placeholder="09..."></label>
    <label>نقش
      <select name="role">
        <?php foreach (ak_role_labels() as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= (string) $person['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>رمز جدید <input name="password" type="password" autocomplete="new-password"></label>
    <label class="check"><input type="checkbox" name="active" <?= (int) $person['active'] === 1 ? 'checked' : '' ?>> فعال</label>
  </div>
  <button type="submit">ذخیره ویرایش</button>
</form>
