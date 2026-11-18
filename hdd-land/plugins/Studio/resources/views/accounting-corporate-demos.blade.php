@if($n === 1)
<div class="cd" id="cd1">
  <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">حسابداری HDD Land · نسخهٔ شرکتی</div><div class="cd-r">شرکت نمونهٔ آریا · سال مالی ۱۴۰۵</div></div>
  <div class="cd-hero">
    <div class="cd-mods" id="cdMods"></div>
    <div class="cd-feed">
      <div class="cd-kpis">
        <div class="cd-kpi"><div class="k">فروش این ماه (میلیون ریال)</div><div class="v num" id="cdK1">۰</div></div>
        <div class="cd-kpi"><div class="k">موجودی نقد و بانک</div><div class="v num" id="cdK2">۰</div></div>
        <div class="cd-kpi"><div class="k">چک‌های سررسید این هفته</div><div class="v num" id="cdK3">۰</div></div>
      </div>
      <div id="cdFeed"></div>
    </div>
  </div>
</div>
@elseif($n === 2)
<div class="cd" id="cd2">
  <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">ترازنامهٔ مقایسه‌ای</div><div class="cd-r">مبالغ به میلیون ریال</div></div>
  <div class="cd-body">
    <div class="cd-tabs"><span>تراز</span><span class="is-on">ترازنامه</span><span>سود و زیان</span><span>جریان وجوه نقد</span><span>طراح صورت‌های مالی</span></div>
    <table>
      <thead><tr><th>شرح</th><th class="n">۱۴۰۵</th><th class="n">۱۴۰۴</th><th class="n hide-m">تغییر</th></tr></thead>
      <tbody id="cdBs"></tbody>
    </table>
    <div class="cd-foot">
      <span class="cd-chip is-ok cd-eq" id="cdEq">✓ جمع دارایی‌ها = جمع بدهی‌ها و حقوق مالکانه</span>
      <span class="cd-exp">Excel</span><span class="cd-exp">PDF</span><span class="cd-exp">چاپ</span>
    </div>
  </div>
</div>
@elseif($n === 3)
<div class="cd" id="cd3">
  <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">ثبت حقوق ماهانه · جامع</div><div class="cd-r">مهر ۱۴۰۵ · مبالغ به ریال</div></div>
  <div class="cd-body">
    <div class="cd-steps" id="cdPst">
      <div class="cd-stp"><b>۱</b>ساعت کاری</div>
      <div class="cd-stp"><b>۲</b>محاسبهٔ حقوق</div>
      <div class="cd-stp"><b>۳</b>سند حقوق</div>
      <div class="cd-stp"><b>۴</b>لیست بیمه، مالیات و بانک</div>
    </div>
    <table>
      <thead><tr><th>پرسنل</th><th class="n">کارکرد (روز)</th><th class="n hide-m">حقوق و مزایا</th><th class="n hide-m">کسورات</th><th class="n">خالص پرداختی</th></tr></thead>
      <tbody id="cdPay"></tbody>
    </table>
    <div class="cd-pay">
      <div class="cd-vch" id="cdVch">
        <div class="h">سند حقوق مهر ۱۴۰۵ · صادر شد</div>
        <div class="l"><span>هزینهٔ حقوق و دستمزد</span><span class="num muted">بدهکار</span></div>
        <div class="l"><span>حقوق پرداختنی</span><span class="num muted">بستانکار</span></div>
        <div class="l"><span>کسورات پرداختنی</span><span class="num muted">بستانکار</span></div>
      </div>
      <div class="cd-files">
        <div class="cd-file">لیست بیمه (کارگاه و کارکنان)</div>
        <div class="cd-file">لیست مالیات حقوق</div>
        <div class="cd-file">فایل پرداخت حقوق به بانک</div>
      </div>
    </div>
  </div>
</div>
@elseif($n === 4)
<div class="cd" id="cd4">
  <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">صف و پایش ارسال به سامانهٔ مؤدیان</div><div class="cd-r">اتصال مستقیم</div></div>
  <div class="cd-body">
    <div class="cd-cnts">
      <div class="cd-cnt"><div class="k">در صف</div><div class="v num" id="cdM0">۰</div></div>
      <div class="cd-cnt"><div class="k">ارسال‌شده</div><div class="v num" id="cdM1">۰</div></div>
      <div class="cd-cnt"><div class="k">تأیید سامانه</div><div class="v num is-green" id="cdM2">۰</div></div>
      <div class="cd-cnt"><div class="k">نیاز به اصلاح</div><div class="v num is-red" id="cdM3">۰</div></div>
    </div>
    <div class="cd-q" id="cdQ"></div>
    <div class="cd-toast" id="cdT4"></div>
  </div>
</div>
@elseif($n === 5)
<div class="cd" id="cd5">
  <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">دفتر دوم و بودجه</div><div class="cd-r">مبالغ به میلیون ریال</div></div>
  <div class="cd-body cd-two">
    <div class="cd-box">
      <h4>دفتر دوم کنار دفتر قانونی</h4>
      <div class="cd-books">
        <div class="cd-book"><div class="h">دفتر اصلی (قانونی)</div>
          <div class="doc">سند ۱۲۴۳ · فروش</div><div class="doc">سند ۱۲۴۴ · خرید</div><div class="doc">سند ۱۲۴۵ · حقوق</div>
          <div class="lock">بدون تغییر</div></div>
        <div class="cd-book" id="cdB2"><div class="h">دفتر دوم</div><div class="muted">فقط اسناد اصلاحیه</div></div>
      </div>
      <table class="cd-recon">
        <thead><tr><th>تراز تطبیقی</th><th class="n">اصلی</th><th class="n">دوم</th><th class="n">اختلاف</th></tr></thead>
        <tbody><tr><td>هزینه‌های اداری</td><td class="n num">۴٬۲۰۰</td><td class="n num" id="cdR2">۴٬۲۰۰</td><td class="n num" id="cdRd">—</td></tr></tbody>
      </table>
      <div class="lock" id="cdTrail">ردپای اصلاحیه در دفتر دوم ثبت شد و پاک‌شدنی نیست</div>
    </div>
    <div class="cd-box">
      <h4>بودجه در برابر عملکرد</h4>
      <div class="cd-bud" id="cdBud"></div>
    </div>
  </div>
</div>
@endif
