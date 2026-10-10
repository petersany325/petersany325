<?php
/** @var array $users */
/** @var array $perms */
/** @var array $edit */
/** @var array $editPerms */
$edit = $edit ?? null;
$editPerms = $editPerms ?? [];
?>
<div class="grid" style="grid-template-columns:1.1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd">
      <strong>کاربران</strong>
      <a class="btn ghost" href="<?= e(url('/settings/profile')) ?>">پروفایل من</a>
    </div>
    <div class="bd">
      <table class="data">
        <thead>
          <tr><th>نام</th><th>ایمیل</th><th>موبایل</th><th>نقش</th><th>وضعیت</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td dir="ltr"><?= e($u['phone'] ?: '—') ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= !isset($u['is_active']) || (int)$u['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?></td>
            <td><a href="<?= e(url('/users?edit=' . (int)$u['id'])) ?>">ویرایش</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="<?= e(url('/users/save')) ?>" class="form" style="padding:12px;border-top:1px solid var(--line)">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="create">
      <strong>کاربر جدید</strong>
      <label>نام<input name="new_name" required></label>
      <label>ایمیل<input name="new_email" type="email" required></label>
      <label>موبایل<input name="new_phone" dir="ltr" placeholder="0912xxxxxxx"></label>
      <label>رمز<input name="new_pass" type="password" required></label>
      <label>نقش
        <select name="new_role">
          <option value="accountant">حسابدار</option>
          <option value="viewer">ناظر</option>
          <option value="admin">مدیر</option>
        </select>
      </label>
      <button class="btn" type="submit">افزودن کاربر</button>
    </form>
  </div>

  <div class="panel">
    <div class="hd"><strong><?= $edit ? 'ویرایش کاربر و دسترسی‌ها' : 'تخصیص دسترسی' ?></strong></div>
    <form method="post" action="<?= e(url('/users/save')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="update">
      <label>کاربر
        <select name="user_id" required onchange="location.href='<?= e(url('/users')) ?>?edit='+this.value">
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $edit && (int)$edit['id']===(int)$u['id']?'selected':'' ?>>
              <?= e($u['name'] . ' (' . $u['email'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($edit): ?>
        <label>نام<input name="name" value="<?= e($edit['name']) ?>" required></label>
        <label>موبایل<input name="phone" value="<?= e($edit['phone'] ?? '') ?>" dir="ltr"></label>
        <label>رمز جدید<input type="password" name="new_pass" placeholder="خالی = بدون تغییر"></label>
        <label><input type="checkbox" name="is_active" <?= !isset($edit['is_active']) || (int)$edit['is_active']===1?'checked':'' ?>> حساب فعال باشد</label>
      <?php endif; ?>
      <label>نقش پایه
        <select name="role">
          <?php foreach (['accountant'=>'حسابدار','viewer'=>'ناظر','admin'=>'مدیر'] as $k=>$lab): ?>
            <option value="<?= e($k) ?>" <?= ($edit['role'] ?? '')===$k?'selected':'' ?>><?= e($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="perm-grid">
        <?php foreach ($perms as $p): ?>
          <label>
            <input type="checkbox" name="perm[]" value="<?= e($p['code']) ?>"
              <?= in_array($p['code'], $editPerms, true) ? 'checked' : '' ?>>
            <?= e($p['title']) ?>
            <span style="color:var(--muted);font-size:11px">(<?= e($p['code']) ?>)</span>
          </label>
        <?php endforeach; ?>
      </div>
      <button class="btn" type="submit">ذخیره کاربر و دسترسی</button>
    </form>
  </div>
</div>
