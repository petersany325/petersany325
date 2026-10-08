<div class="panel">
  <div class="hd"><strong>مرکز گزارش‌ها</strong></div>
  <div class="bd">
    <div class="report-grid">
      <a href="<?= e(url('/trial-balance')) ?>">تراز آزمایشی (۲/۴/۶ ستونی)</a>
      <a href="<?= e(url('/reports/balance-sheet')) ?>">ترازنامه</a>
      <a href="<?= e(url('/reports/pl')) ?>">سود و زیان جامع</a>
      <a href="<?= e(url('/ledger')) ?>">دفتر معین / مرور حساب</a>
      <a href="<?= e(url('/reports/journal')) ?>">دفتر روزنامه</a>
      <a href="<?= e(url('/reports/nature-violations')) ?>">اسناد خلاف ماهیت</a>
      <a href="<?= e(url('/reports/share')) ?>">سهم‌بری حساب‌ها / پروژه</a>
      <a href="<?= e(url('/reports/checks')) ?>">اسناد دریافتنی و پرداختنی</a>
      <a href="<?= e(url('/reports/bank-reconcile')) ?>">مغایرت بانکی</a>
      <a href="<?= e(url('/reports/charts')) ?>">بررسی نموداری دوره‌ها</a>
      <a href="<?= e(url('/audit')) ?>">تاریخچه فعالیت کاربران</a>
    </div>
    <p style="color:var(--muted);margin-top:14px">خروجی Excel از داخل هر گزارش با پارامتر <code>?excel=1</code> در دسترس است.</p>
  </div>
</div>
