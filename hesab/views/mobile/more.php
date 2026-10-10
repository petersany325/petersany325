<div class="m-card">
  <div class="m-card-hd">
    <strong>منوی بیشتر</strong>
    <button type="button" class="m-btn ghost" onclick="document.getElementById('m-menu-btn')?.click()">باز کردن منو ☰</button>
  </div>
  <div class="m-grid-links">
    <a href="<?= e(url('/m/reports')) ?>"><strong>گزارش‌ها</strong><span>تراز، سود و زیان، دفاتر</span></a>
    <a href="<?= e(url('/m/accounts')) ?>"><strong>کدینگ</strong><span>مرور حساب‌های معین</span></a>
    <a href="<?= e(url('/m/tafsili')) ?>"><strong>تفصیلی</strong><span>اشخاص و شناورها</span></a>
    <a href="<?= e(url('/parties')) ?>"><strong>طرف‌حساب</strong><span>مشتری و تأمین‌کننده</span></a>
    <a href="<?= e(url('/m/treasury')) ?>"><strong>خزانه</strong><span>دریافت و پرداخت</span></a>
    <a href="<?= e(url('/cheques')) ?>"><strong>چک‌ها</strong><span>اسناد دریافتنی/پرداختنی</span></a>
    <a href="<?= e(url('/visitors')) ?>"><strong>ویزیتور</strong><span>پخش و کارتابل</span></a>
    <a href="<?= e(url('/invoices')) ?>"><strong>فاکتور فروش</strong><span>صدور فاکتور</span></a>
    <a href="<?= e(url('/?desktop=1')) ?>"><strong>نسخه ویندوزی</strong><span>پوسته دسکتاپ کامل</span></a>
    <a href="<?= e(url('/logout')) ?>"><strong>خروج</strong><span><?= e($user['email'] ?? '') ?></span></a>
  </div>
</div>
