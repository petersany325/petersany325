<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>کاربران</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="<?= e(url('/users/save')) ?>" class="form" style="padding:12px;border-top:1px solid var(--line)">
      <?= csrf_field() ?>
      <strong>کاربر جدید</strong>
      <label>نام<input name="new_name" required></label>
      <label>ایمیل<input name="new_email" type="email" required></label>
      <label>رمز<input name="new_pass" type="password" required></label>
      <label>نقش
        <select name="new_role">
          <option value="accountant">حسابدار</option>
          <option value="viewer">ناظر</option>
          <option value="admin">مدیر</option>
        </select>
      </label>
      <button class="btn" type="submit">افزودن</button>
    </form>
  </div>

  <div class="panel">
    <div class="hd"><strong>تخصیص دسترسی کاربر</strong></div>
    <form method="post" action="<?= e(url('/users/save')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>کاربر
        <select name="user_id" required>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= e($u['name'] . ' (' . $u['email'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>نقش پایه
        <select name="role">
          <option value="accountant">حسابدار</option>
          <option value="viewer">ناظر</option>
          <option value="admin">مدیر</option>
        </select>
      </label>
      <div class="perm-grid">
        <?php foreach ($perms as $p): ?>
          <label><input type="checkbox" name="perm[]" value="<?= e($p['code']) ?>"> <?= e($p['title']) ?></label>
        <?php endforeach; ?>
      </div>
      <button class="btn" type="submit">ذخیره دسترسی</button>
    </form>
  </div>
</div>
