@if($n === 1)
<div class="rd" id="rd1">
  <div class="rd-bar"><div class="rd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="rd-t">ثبت سفارش · صفحهٔ لمسی</div><div class="rd-r">سالن · میز ۷ · ۴ نفر · گارسون: علی</div></div>
  <div class="rd-touch">
    <div class="rd-menu"><div class="rd-cats" id="rdCats"></div><div class="rd-tiles" id="rdTiles"></div></div>
    <div class="rd-ord">
      <div class="rd-oh"><span>سفارش ۳۱۲ · سالن</span><span>میز ۷</span></div>
      <div id="rdOl"></div>
      <div class="rd-grow"></div>
      <div class="rd-tl"><span class="muted">جمع</span><span class="num" id="rdO1">۰</span></div>
      <div class="rd-tl"><span class="muted">حق سرویس</span><span class="num" id="rdO2">۰</span></div>
      <div class="rd-tl is-big"><span>قابل پرداخت</span><span class="num" id="rdO3">۰</span></div>
      <div class="rd-acts"><span class="rd-btn is-p" id="rdBk">ارسال به آشپزخانه</span><span class="rd-btn">تسویه</span></div>
    </div>
  </div>
  <div class="rd-toast" id="rdT1"></div>
</div>
@elseif($n === 2)
<div class="rd" id="rd2">
  <div class="rd-bar"><div class="rd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="rd-t">نمای سالن و میزها</div><div class="rd-r">هر ۳۰ ثانیه خودکار تازه می‌شود</div></div>
  <div class="rd-sum"><span>میزها <b class="num" id="rdSa">۰</b></span><span class="is-free">آزاد <b class="num" id="rdSf">۰</b></span><span class="is-busy">دارای سفارش <b class="num" id="rdSb">۰</b></span><span class="is-res">رزرو <b class="num" id="rdSr">۰</b></span><span class="rd-btn">بیرون‌بر جدید</span></div>
  <div class="rd-halls">
    <div class="rd-hall"><div class="h">سالن اصلی</div><div class="rd-tbls" id="rdH1"></div></div>
    <div class="rd-hall"><div class="h">تراس</div><div class="rd-tbls" id="rdH2"></div></div>
  </div>
</div>
@elseif($n === 3)
<div class="rd" id="rd3">
  <div class="rd-bar"><div class="rd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="rd-t">سفارش گارسون با گوشی اندروید</div><div class="rd-r">اندروید ۱۲ و بالاتر</div></div>
  <div class="rd-two">
    <div class="rd-phone"><div class="rd-ph-top"><span>۲۰:۱۴</span><span>گارسون: علی</span></div><div class="rd-ph-h" id="rdWt">انتخاب میز</div><div class="rd-ph-b" id="rdWb"></div></div>
    <div class="rd-leg">
      <div class="h">وضعیت هر قلم روی گوشی گارسون</div>
      <div class="row"><span class="rd-chip is-new">جدید</span><span class="arr">←</span><span class="rd-chip is-prep">در تهیه</span><span class="arr">←</span><span class="rd-chip is-ready">آماده</span><span class="arr">←</span><span class="rd-chip is-served">سرو شد</span></div>
      <div class="muted">گارسون میز را انتخاب می‌کند، غذا اضافه می‌کند و برای هر قلم توضیح می‌نویسد. قیمت و مالیات همیشه از سرور می‌آید، نه از گوشی.</div>
    </div>
  </div>
</div>
@elseif($n === 4)
<div class="rd" id="rd4">
  <div class="rd-bar"><div class="rd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="rd-t">منوی QR میز</div><div class="rd-r">سفارش مشتری فقط با تأیید کارکنان ثبت می‌شود</div></div>
  <div class="rd-qr">
    <div class="rd-qrcard">میز ۱۲<svg viewBox="0 0 21 21" id="rdQr" shape-rendering="crispEdges" aria-hidden="true"></svg>منو را اسکن کنید<div class="rd-scan" id="rdSl"></div></div>
    <div class="rd-phone is-short"><div class="rd-ph-top"><span>منوی رستوران نمونه</span><span>میز ۱۲</span></div><div class="rd-ph-h">غذای اصلی</div><div class="rd-ph-b" id="rdQb"></div></div>
    <div class="rd-staff"><div class="h">سفارش‌های QR · صندوق‌دار</div><div id="rdQs"><div class="muted">سفارشی در انتظار نیست.</div></div></div>
  </div>
  <div class="rd-toast" id="rdT4"></div>
</div>
@elseif($n === 5)
<div class="rd" id="rd5">
  <div class="rd-bar"><div class="rd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="rd-t">صف آشپزخانه</div><div class="rd-r" id="rdClk">ساعت ۲۰:۱۴</div></div>
  <div class="rd-body">
    <div class="rd-stn" id="rdStn"><span>همه</span><span>آشپزخانه</span><span>قنادی</span></div>
    <table><thead><tr><th>سفارش</th><th class="hide-m">ساعت ارسال</th><th>میز</th><th class="hide-m">ایستگاه</th><th>قلم</th><th class="hide-m">توضیح</th><th>وضعیت</th><th class="n">دقیقه</th></tr></thead><tbody id="rdKq"></tbody></table>
  </div>
</div>
@elseif($n === 6)
<div class="rd" id="rd6">
  <div class="rd-bar"><div class="rd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="rd-t">دستور پخت · چلوکباب کوبیده (یک پرس)</div><div class="rd-r">مبالغ به ریال</div></div>
  <div class="rd-body rd-rcp">
    <div class="rd-box"><h4>مواد یک پرس</h4>
      <table><thead><tr><th>ماده</th><th class="n">مقدار</th><th class="n">بها</th></tr></thead><tbody id="rdRc"></tbody></table>
      <div class="rd-kpis"><div class="rd-kp"><div class="k">بهای هر پرس</div><div class="v num" id="rdCp">۰</div></div><div class="rd-kp"><div class="k">قیمت فروش</div><div class="v num" id="rdSp">۰</div></div><div class="rd-kp"><div class="k">سود هر پرس</div><div class="v num is-plus" id="rdPp">۰</div></div></div>
    </div>
    <div class="rd-box"><h4>موجودی مواد · <span class="muted" id="rdEv">پیش از تسویه</span></h4>
      <div class="rd-stk" id="rdStk"></div>
      <div class="rd-warn" id="rdWr">گوشت چرخ‌کرده کمتر از حداقل موجودی شد</div>
    </div>
  </div>
</div>
@endif
