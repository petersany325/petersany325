@if($n === 1)
<div class="sd" id="sd1">
  <div class="sd-bar"><div class="sd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sd-t">فاکتور فروش</div><div class="sd-r">مشتری: آقای نمونه · نقد و نسیه</div></div>
  <div class="sd-body">
    <div class="sd-scan" id="sdScan"><span>بارکد</span><span class="bc" id="sdBc">—</span><span class="muted">یا جست‌وجوی کالا</span></div>
    <table>
      <thead><tr><th>کالا</th><th class="n">تعداد</th><th class="n hide-m">فی (ریال)</th><th class="n hide-m">تخفیف</th><th class="n">مبلغ (ریال)</th></tr></thead>
      <tbody id="sdInv"></tbody>
    </table>
    <div class="sd-foot">
      <div>
        <div class="sd-chip is-ok sd-profit" id="sdProf">سود این فاکتور: <span class="num" id="sdProfv">۰</span> ریال</div>
        <div class="sd-keys"><span class="sd-key" id="sdF9">F9</span> تسویه <span class="sd-key" id="sdF10">F10</span> ثبت</div>
      </div>
      <div class="sd-tot">
        <div class="l"><span class="muted">جمع</span><span class="num" id="sdSum">۰</span></div>
        <div class="l is-disc"><span>تخفیف پلکانی</span><span class="num" id="sdDsc">۰</span></div>
        <div class="l is-big"><span>قابل پرداخت</span><span class="num" id="sdNet">۰</span></div>
      </div>
    </div>
    <div class="sd-toast" id="sdT1"></div>
  </div>
</div>
@elseif($n === 2)
<div class="sd" id="sd2">
  <div class="sd-bar"><div class="sd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sd-t">تسویهٔ فاکتور (F9)</div><div class="sd-r">فاکتور ۴۸۲۱ · مبلغ ۴۵٬۵۰۵٬۰۰۰ ریال</div></div>
  <div class="sd-body sd-settle">
    <div id="sdPms">
      <div class="sd-pm" data-i="0"><div><div class="nm">نقد (صندوق)</div><div class="sub">&nbsp;</div></div><span class="am">۰</span></div>
      <div class="sd-pm" data-i="1"><div><div class="nm">کارتخوان <span class="sd-key">F5</span></div><div class="sub">&nbsp;</div></div><span class="am">۰</span></div>
      <div class="sd-pm" data-i="2"><div><div class="nm">کارت به کارت <span class="sd-key">F5</span></div><div class="sub">&nbsp;</div></div><span class="am">۰</span></div>
      <div class="sd-pm" data-i="3"><div><div class="nm">چک <span class="sd-key">F7</span></div><div class="sub">&nbsp;</div></div><span class="am">۰</span></div>
    </div>
    <div class="sd-rem">
      <div><div class="k">باقی‌مانده (نسیه)</div><div class="v num" id="sdRem">۴۵٬۵۰۵٬۰۰۰</div></div>
      <div class="sd-meter" id="sdMeter"><span></span><span></span><span></span><span></span></div>
      <div class="sd-legend"><span>نقد</span><span>کارتخوان</span><span>کارت به کارت</span><span>چک</span></div>
      <div class="muted">هر چه تسویه نشود، نسیه به حساب مشتری می‌رود.</div>
      <div class="sd-keys"><span class="sd-key" id="sdK2">F10</span> تأیید تسویه و ثبت فاکتور</div>
    </div>
    <div class="sd-toast" id="sdT2"></div>
  </div>
</div>
@elseif($n === 3)
<div class="sd" id="sd3">
  <div class="sd-bar"><div class="sd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sd-t">گزارش موجودی کالا</div><div class="sd-r">تعدادی و ریالی</div></div>
  <div class="sd-body">
    <div class="sd-wh" id="sdWh"><span>همهٔ انبارها</span><span>انبار مرکزی</span><span>فروشگاه</span></div>
    <table>
      <thead><tr><th>کالا</th><th class="n">موجودی</th><th class="n hide-m">نقطهٔ سفارش</th><th class="hide-m"></th><th class="n hide-m">ارزش (ریال)</th><th>وضعیت</th></tr></thead>
      <tbody id="sdStk"></tbody>
    </table>
    <div class="sd-suggest" id="sdSug">پیشنهاد سفارش بر اساس میانگین فروش: سشوار حرفه‌ای ۲۴ عدد · چای‌ساز رومیزی ۱۲ عدد</div>
  </div>
</div>
@elseif($n === 4)
<div class="sd" id="sd4">
  <div class="sd-bar"><div class="sd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sd-t">چک‌های دریافتی</div><div class="sd-r">وضعیت جاری و سررسید</div></div>
  <div class="sd-body">
    <div class="sd-remind" id="sdRmd">یادآور چک: ۱ چک سررسید امروز و ۲ چک تا ۷ روز آینده</div>
    <div class="sd-cols">
      <div class="sd-col" id="sdC0"><div class="h"><span>نزد صندوق</span><span class="num muted">۰</span></div></div>
      <div class="sd-col" id="sdC1"><div class="h"><span>واگذارشده به بانک</span><span class="num muted">۰</span></div></div>
      <div class="sd-col is-ok" id="sdC2"><div class="h"><span>وصول‌شده</span><span class="num">۰</span></div></div>
      <div class="sd-col is-bad" id="sdC3"><div class="h"><span>برگشتی</span><span class="num">۰</span></div></div>
    </div>
  </div>
</div>
@elseif($n === 5)
<div class="sd" id="sd5">
  <div class="sd-bar"><div class="sd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sd-t">جامع درصد فروش و سود</div><div class="sd-r">مهر ۱۴۰۵ · مبالغ به میلیون ریال</div></div>
  <div class="sd-body">
    <div class="sd-grp" id="sdGrp"><span>کالا</span><span>رده کالا</span><span>فاکتور</span><span>مشتری</span><span>روز</span></div>
    <table>
      <thead><tr><th>کالا</th><th class="n hide-m">فروش خالص</th><th class="n hide-m">بهای تمام‌شده</th><th class="n">سود ناخالص</th><th>سهم از کل سود</th></tr></thead>
      <tbody id="sdPrf"></tbody>
    </table>
  </div>
</div>
@endif
