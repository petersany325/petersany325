@if($n === 1)
<div class="dd" id="dd1">
  <div class="dd-bar"><div class="dd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="dd-t">نقشهٔ پخش · امروز</div><div class="dd-r" id="ddClk">آخرین مکان: ۱۰:۴۲</div></div>
  <div class="dd-map">
    <svg viewBox="0 0 600 300" id="ddSvg" aria-hidden="true">
      <g stroke="#1d2142" stroke-width="10" fill="none" stroke-linecap="round">
        <path d="M0 250 H600"/><path d="M0 160 H600"/><path d="M0 75 H600"/>
        <path d="M90 0 V300"/><path d="M230 0 V300"/><path d="M370 0 V300"/><path d="M510 0 V300"/>
        <path d="M0 300 L300 0"/>
      </g>
      <g fill="#14183a"><rect x="110" y="95" width="100" height="45" rx="6"/><rect x="250" y="180" width="100" height="50" rx="6"/><rect x="390" y="185" width="100" height="45" rx="6"/><rect x="390" y="95" width="100" height="45" rx="6"/></g>
      <path id="ddVp" d="M40 250 H90 V160 H230 V75 H370 V160 H510 V75 H570" fill="none" stroke="#4fa8ff" stroke-width="2.5" stroke-dasharray="6 6"/>
      <path id="ddTp" d="M560 250 H370 V160 L300 160 H230 V250 H90 V75" fill="none" stroke="#2dd4bf" stroke-width="3"/>
      <g id="ddPins"></g>
      <g id="ddVis"><circle r="13" fill="none" stroke="#4fa8ff" stroke-width="2" class="dd-pulse"/><circle r="8" fill="#0e1124" stroke="#4fa8ff" stroke-width="3"/></g>
      <g id="ddTrk"><circle r="13" fill="none" stroke="#2dd4bf" stroke-width="2" class="dd-pulse"/><circle r="8" fill="#0e1124" stroke="#2dd4bf" stroke-width="3"/></g>
    </svg>
    <div class="dd-side">
      <div class="lg"><i style="background:#2ecc8f"></i>سفارش یا تحویل</div>
      <div class="lg"><i style="background:#f59e42"></i>بی‌خرید یا تحویل نشد</div>
      <div class="lg"><i style="background:#4fa8ff"></i>در راه</div>
      <div class="lg"><i style="background:#6b7099"></i>ویزیت نشده</div>
      <div class="sep"></div>
      <div class="lg"><i style="border:2px solid #4fa8ff"></i>ویزیتور · مسیر خط‌چین</div>
      <div class="lg"><i style="border:2px solid #2dd4bf"></i>ماشین · مسیر خط پر</div>
      <div class="sep"></div>
      <div class="dd-tally"><span class="muted">سفارش</span><b class="num" id="ddN0">۰</b><span class="muted">بی‌خرید</span><b class="num" id="ddN1">۰</b><span class="muted">تحویل‌شده</span><b class="num" id="ddN2">۰</b></div>
    </div>
  </div>
</div>
@elseif($n === 2)
<div class="dd" id="dd2">
  <div class="dd-bar"><div class="dd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="dd-t">اپ اندروید ویزیتور</div><div class="dd-r">حداقل اندروید ۱۲</div></div>
  <div class="dd-app">
    <div class="dd-phone">
      <div class="dd-ph-top"><span>۱۰:۴۲</span><span class="dd-net is-off" id="ddNet">بدون اینترنت</span></div>
      <div class="dd-ph-h" id="ddCust">سوپرمارکت ستاره</div>
      <div class="dd-ph-b" id="ddItems"></div>
      <div class="dd-ph-btn" id="ddSend">ثبت سفارش</div>
      <div class="dd-ph-st" id="ddPst"></div>
    </div>
    <div class="dd-route">
      <div class="h">مسیر امروز · ویزیتور: رضا کریمی</div>
      <div class="dd-rc" data-i="0"><span>سوپرمارکت ستاره</span><span class="dd-chip">ویزیت نشده</span></div>
      <div class="dd-rc" data-i="1"><span>بقالی امید</span><span class="dd-chip">ویزیت نشده</span></div>
      <div class="dd-rc" data-i="2"><span>فروشگاه گلستان</span><span class="dd-chip">ویزیت نشده</span></div>
      <div class="dd-rc" data-i="3"><span>مینی‌مارکت باران</span><span class="dd-chip">ویزیت نشده</span></div>
      <div class="muted">طرح‌ها و اشانتیون بی‌اینترنت هم روی گوشی دیده می‌شود. سفارش با برگشت اینترنت خودش می‌رسد و فقط یک بار ثبت می‌شود.</div>
    </div>
  </div>
</div>
@elseif($n === 3)
<div class="dd" id="dd3">
  <div class="dd-bar"><div class="dd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="dd-t">لیست پخش · ماشین ۲ · موزع: علی رضایی</div><div class="dd-r">انبار ماشین جدا</div></div>
  <div class="dd-body dd-disp">
    <div class="dd-pane"><div class="h">سفارش‌های تأییدشده برای لیست پخش</div><div id="ddAppr"></div></div>
    <div class="dd-pane">
      <div class="h"><span>بار ماشین و ترتیب تحویل</span><span class="dd-btn" id="ddSort">ترتیب بر اساس فاصله</span></div>
      <div id="ddLoad"></div>
      <div class="dd-cap"><div class="l"><span class="muted">وزن بار</span><span class="num" id="ddCapv">۰٪ ظرفیت</span></div><div class="trk"><i id="ddCapb"></i></div></div>
      <div class="dd-acts"><span class="dd-btn" id="ddLoadBtn">بارگیری و صدور فاکتورها</span><span class="dd-btn" id="ddSet">تسویهٔ پایان روز ماشین</span></div>
      <div class="dd-settle" id="ddSettle">
        <div class="l"><span>بار برگشتی شمارش‌شده</span><b class="num">۴ کارتن</b></div>
        <div class="l"><span>کسری</span><b class="num">۰</b></div>
        <div class="l"><span>وجه نقد موزع</span><b class="num">۳۸٬۲۰۰٬۰۰۰ ریال</b></div>
        <div class="l"><span>پورسانت موزع</span><b class="num">سند خودکار</b></div>
      </div>
    </div>
    <div class="dd-toast" id="ddT3"></div>
  </div>
</div>
@elseif($n === 4)
<div class="dd" id="dd4">
  <div class="dd-bar"><div class="dd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="dd-t">اشانتیون پلکانی · ماست ۹۰۰ گرمی</div><div class="dd-r">پایهٔ پله‌ها: تعداد کارتن</div></div>
  <div class="dd-body dd-sch">
    <div>
      <div class="dd-ladder"><div class="dd-ltrack"><div class="dd-lfill" id="ddLf"></div>
        <div class="dd-tick" style="inset-inline-start:25%">۱۰ کارتن</div>
        <div class="dd-tick" style="inset-inline-start:50%">۲۰ کارتن</div>
        <div class="dd-tick" style="inset-inline-start:100%;transform:translateX(15%)">۴۰ کارتن</div>
      </div></div>
      <div class="dd-tiers">
        <div class="dd-tier" data-t="10">پلهٔ ۱: از <b class="num">۱۰</b><br>اشانتیون <b class="num">۱</b> کارتن</div>
        <div class="dd-tier" data-t="20">پلهٔ ۲: از <b class="num">۲۰</b><br>اشانتیون <b class="num">۳</b> کارتن</div>
        <div class="dd-tier" data-t="40">پلهٔ ۳: از <b class="num">۴۰</b><br>اشانتیون <b class="num">۷</b> کارتن</div>
      </div>
      <div class="dd-hint" id="ddNx">۱۰ کارتن دیگر ← پلهٔ بعد</div>
    </div>
    <div>
      <div class="muted">سفارش گوشی ویزیتور · طرح خودکار اعمال می‌شود</div>
      <div class="dd-big num" id="ddQ">۰ <span class="muted">کارتن</span></div>
      <div class="dd-line"><span>ماست ۹۰۰ گرمی</span><span class="num" id="ddRq">۰ کارتن</span></div>
      <div class="dd-line is-gift" id="ddGr" hidden><span>اشانتیون · تخفیف ۱۰۰٪</span><span class="num" id="ddGq">۰ کارتن</span></div>
      <div class="muted">اشانتیون ردیف جدا با تخفیف ۱۰۰٪ است، از انبار خارج می‌شود و پورسانت ندارد.</div>
    </div>
  </div>
</div>
@elseif($n === 5)
<div class="dd" id="dd5">
  <div class="dd-bar"><div class="dd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="dd-t">داشبورد مدیر پخش</div><div class="dd-r">امروز و مهر ۱۴۰۵</div></div>
  <div class="dd-body">
    <div class="dd-kp">
      <div class="dd-kc"><div class="k">پوشش مسیر امروز</div><div class="v num"><span data-c="86">۰</span>٪</div><div class="s">۴۸ از ۵۶ مشتری · ۷ بی‌خرید</div><div class="dd-ring"><i data-w="86"></i></div></div>
      <div class="dd-kc"><div class="k">سفارش ← تحویل (ماه)</div><div class="v num"><span data-c="94">۰</span>٪</div><div class="s">تحویل کامل یا با برگشتی</div><div class="dd-ring"><i data-w="94" style="background:#2ecc8f"></i></div></div>
      <div class="dd-kc"><div class="k">فروش ماه / هدف</div><div class="v num"><span data-c="78">۰</span>٪</div><div class="s">از سقف ریالی سهمیه‌ها</div><div class="dd-ring"><i data-w="78" style="background:#4fa8ff"></i></div></div>
      <div class="dd-kc"><div class="k">برگشت از فروش (ماه)</div><div class="v num"><span data-c="2">۰</span>٪</div><div class="s">برگشتی ÷ فروش پخش</div><div class="dd-ring"><i data-w="2" style="background:#f59e42"></i></div></div>
    </div>
    <table>
      <thead><tr><th>ویزیتور</th><th class="n hide-m">مشتری مسیر</th><th class="n">ویزیت امروز</th><th class="n hide-m">بی‌خرید</th><th class="n hide-m">ثبت دور از مغازه</th><th>تحقق هدف</th></tr></thead>
      <tbody id="ddVt"></tbody>
    </table>
  </div>
</div>
@endif
