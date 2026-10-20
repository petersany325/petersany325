<?php
/** @var bool $files */
?>
<section class="section"><div class="wrap">
  <p class="kicker">همکاری</p>
  <h1>درخواست نمایندگی</h1>
  <p class="lead">این فرم برای بررسی اولیه است. ارسال آن نمایندگی، کاربر ورود یا حساب مالی نمی‌سازد.</p>
  <form method="post" action="<?= e(url('/dealership')) ?>" class="stack"<?= $files ? ' enctype="multipart/form-data"' : '' ?>>
    <?= csrf_field() ?>
    <div class="grid">
      <label>نام <input name="name" required></label>
      <label>موبایل <input name="phone" required inputmode="numeric" placeholder="09120000000"></label>
      <label>استان <input name="province"></label>
      <label>شهر <input name="city" required></label>
      <label>نام فروشگاه <input name="shop"></label>
    </div>
    <label>شرح سابقه و محدوده کار <textarea name="body"<?= $files ? '' : ' required' ?>></textarea></label>
    <?php if ($files): ?>
      <label>پیوست PDF، JPG یا TXT، اختیاری <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt"></label>
    <?php endif; ?>
    <button class="btn" type="submit">ارسال درخواست</button>
  </form>
</div></section>
