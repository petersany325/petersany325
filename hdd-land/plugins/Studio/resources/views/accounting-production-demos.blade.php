@if($n === 1)
<div class="mf" id="mf1">
  <div class="mf-bar"><div class="mf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="mf-t">دستور تولید</div><div class="mf-r">مبالغ به ریال</div></div>
  <div class="mf-body">
    <div class="mf-po">
      <div class="mf-box">
        <div class="mf-fld"><span>محصول</span><span class="mf-inp" id="mfP0"></span></div>
        <div class="mf-fld"><span>مقدار</span><span class="mf-inp" id="mfP1"></span></div>
        <div class="mf-fld"><span>انبار</span><span class="mf-inp" id="mfP2"></span></div>
        <table><thead><tr><th>مصرف مواد (به نسبت مقدار)</th><th class="n">مقدار</th><th class="n">بها (میانگین موزون)</th></tr></thead><tbody id="mfMat"></tbody></table>
        <div class="mf-acts"><span class="mf-btn is-p" id="mfPb">ثبت دستور تولید</span></div>
      </div>
      <div class="mf-vch">
        <div class="mf-vc" id="mfV1"><div class="h"><span>۱ · حوالهٔ مواد</span><span class="mf-chip is-ok">خودکار</span></div><div class="l"><span>کالای در جریان ساخت</span><span>بدهکار</span></div><div class="l"><span>موجودی مواد اولیه</span><span>بستانکار</span></div></div>
        <div class="mf-vc" id="mfV2"><div class="h"><span>۲ · جذب دستمزد و سربار</span><span class="mf-chip is-ok">خودکار</span></div><div class="l"><span>کالای در جریان ساخت</span><span>بدهکار</span></div><div class="l"><span>دستمزد و سربار جذب‌شده</span><span>بستانکار</span></div></div>
        <div class="mf-vc" id="mfV3"><div class="h"><span>۳ · رسید محصول</span><span class="mf-chip is-ok">خودکار</span></div><div class="l"><span>موجودی محصول</span><span>بدهکار</span></div><div class="l"><span>کالای در جریان ساخت</span><span>بستانکار</span></div></div>
      </div>
    </div>
    <div class="mf-kp">
      <div class="mf-k"><div class="k">بهای مواد</div><div class="v num" id="mfK1">۰</div></div>
      <div class="mf-k"><div class="k">دستمزد و سربار</div><div class="v num" id="mfK2">۰</div></div>
      <div class="mf-k"><div class="k">بهای تمام‌شده</div><div class="v num" id="mfK3">۰</div></div>
      <div class="mf-k is-main"><div class="k">بهای هر واحد</div><div class="v num" id="mfK4">۰</div></div>
    </div>
  </div>
</div>
@elseif($n === 2)
<div class="mf" id="mf2">
  <div class="mf-bar"><div class="mf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="mf-t">فرمول ساخت · ماست موسیر</div><div class="mf-r">مقدار خروجی فرمول: ۱۰۰ عدد</div></div>
  <div class="mf-body mf-bom">
    <div class="mf-tree" id="mfTree"></div>
    <div>
      <div class="mf-box"><h4>گزارش پارت لیست</h4>
        <table><thead><tr><th>جزء</th><th>نوع</th><th class="n">برای یک واحد</th><th class="n hide-m">بهای میانگین</th></tr></thead><tbody id="mfPl"></tbody></table>
      </div>
      <div class="mf-rules" id="mfRules">
        <div class="mf-rule"><span class="mf-chip is-bad">✕</span>محصول نمی‌تواند مادهٔ خودش باشد</div>
        <div class="mf-rule"><span class="mf-chip is-bad">✕</span>یک ماده دو بار در فرمول نمی‌آید</div>
        <div class="mf-rule"><span class="mf-chip is-bad">✕</span>مقدار منفی پذیرفته نمی‌شود</div>
      </div>
    </div>
  </div>
</div>
@elseif($n === 3)
<div class="mf" id="mf3">
  <div class="mf-bar"><div class="mf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="mf-t">مانده محصول بر اساس پارت لیست</div><div class="mf-r">با موجودی فعلی چند واحد ساخته می‌شود؟</div></div>
  <div class="mf-body">
    <div class="mf-wh" id="mfWh"><span>همهٔ انبارها</span><span>انبار مرکزی</span><span>انبار پیمانکار</span></div>
    <table><thead><tr><th>محصول</th><th>قابل ساخت</th><th class="n">تعداد</th><th>مادهٔ کمبود</th></tr></thead><tbody id="mfCap"></tbody></table>
  </div>
</div>
@elseif($n === 4)
<div class="mf" id="mf4">
  <div class="mf-bar"><div class="mf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="mf-t">سری‌های ساخت · ماست ۱ کیلویی</div><div class="mf-r" id="mfToday">امروز: ۱۴۰۵/۰۸/۲۶</div></div>
  <div class="mf-body mf-bt">
    <table><thead><tr><th>سری ساخت</th><th class="hide-m">تاریخ تولید</th><th>انقضا</th><th class="n">مانده</th><th>وضعیت</th></tr></thead><tbody id="mfBts"></tbody></table>
    <div class="mf-pick" id="mfPick"><b>فاکتور فروش · ۴۰ عدد</b><div class="muted">انتخاب سری…</div></div>
  </div>
</div>
@elseif($n === 5)
<div class="mf" id="mf5">
  <div class="mf-bar"><div class="mf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="mf-t">قبض باسکول · آرد</div><div class="mf-r">ماشین ۲۴ ع ۱۲۳ · راننده: نمونه</div></div>
  <div class="mf-body mf-wb">
    <div class="mf-scene"><div class="mf-disp" id="mfWd">0 kg</div><div class="mf-ground"></div><div class="mf-plat"></div>
      <div class="mf-truck" id="mfTrk"><svg viewBox="0 0 150 60" aria-hidden="true"><rect x="0" y="6" width="100" height="38" rx="4" fill="#3b4382"/><rect id="mfLoad" x="0" y="6" width="100" height="38" rx="4" fill="url(#mfLd)"/><defs><linearGradient id="mfLd" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#e8dcc0"/><stop offset="1" stop-color="#bba98a"/></linearGradient></defs><path d="M102 18h26l16 14v12h-42z" fill="#5a63b5"/><rect x="110" y="22" width="16" height="10" rx="2" fill="#9fb4ff"/><circle cx="22" cy="48" r="8" fill="#1a1d38" stroke="#9097c0" stroke-width="2"/><circle cx="78" cy="48" r="8" fill="#1a1d38" stroke="#9097c0" stroke-width="2"/><circle cx="128" cy="48" r="8" fill="#1a1d38" stroke="#9097c0" stroke-width="2"/></svg></div>
    </div>
    <div class="mf-box mf-calc">
      <div class="mf-cl" id="mfC1"><span>توزین اول (پر)</span><b class="num">۴۲٬۰۰۰ کیلو</b></div>
      <div class="mf-cl" id="mfC2"><span>توزین دوم (خالی)</span><b class="num">۱۵٬۰۰۰ کیلو</b></div>
      <div class="mf-cl" id="mfC3"><span>وزن خالص</span><b class="num">۲۷٬۰۰۰ کیلو</b></div>
      <div class="mf-cl" id="mfC4"><span>کسر رطوبت ۱٪</span><b class="num">۲۷۰ کیلو</b></div>
      <div class="mf-cl is-fin" id="mfC5"><span>پذیرفته</span><b class="num">۲۶٬۷۳۰ کیلو</b></div>
      <div class="mf-acts"><span class="mf-btn is-p" id="mfWbtn">ساخت فاکتور خرید</span><span class="mf-btn">رسید انبار</span></div>
    </div>
  </div>
  <div class="mf-toast" id="mfT5"></div>
</div>
@elseif($n === 6)
<div class="mf" id="mf6">
  <div class="mf-bar"><div class="mf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="mf-t">گزارش تولید و بهای تمام‌شده</div><div class="mf-r">مهر ۱۴۰۵ · مبالغ به ریال</div></div>
  <div class="mf-body">
    <table><thead><tr><th>محصول</th><th class="n hide-m">تعداد دستور</th><th class="n hide-m">مقدار تولید</th><th class="n">میانگین بهای واحد</th><th class="n hide-m">قیمت فروش</th><th>ترکیب بها</th></tr></thead><tbody id="mfCr"></tbody></table>
    <div class="mf-leg"><span><i class="m"></i>مواد</span><span><i class="o"></i>دستمزد و سربار</span><span><i class="p"></i>قیمت فروش</span></div>
  </div>
  <div class="mf-toast is-bad" id="mfT6"></div>
</div>
@endif
