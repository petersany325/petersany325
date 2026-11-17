@if($n === 1)
<div class="cf" id="cf1">
  <div class="cf-bar"><div class="cf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cf-t">سفارش پیشخوان</div><div class="cf-r">بیرون‌بر · صف صبح</div></div>
  <div class="cf-ctr">
    <div class="cf-menu"><div class="cf-cats" id="cfCats"></div><div class="cf-tiles" id="cfTiles"></div></div>
    <div class="cf-ord"><div class="muted">سفارش ۸۴ · پیشخوان</div><div id="cfOl"></div><div class="cf-grow"></div>
      <div class="cf-tl"><span>جمع</span><span class="num" id="cfTot">۰</span></div>
      <div class="cf-acts"><span class="cf-btn is-p" id="cfBb">ارسال به بار</span><span class="cf-btn">تسویه</span></div>
    </div>
    <div class="cf-modal" id="cfMod"><div class="cf-mbox" id="cfMbox"></div></div>
  </div>
  <div class="cf-toast" id="cfT1"></div>
</div>
@elseif($n === 2)
<div class="cf" id="cf2">
  <div class="cf-bar"><div class="cf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cf-t">صف بار · باریستا</div><div class="cf-r" id="cfClk">ساعت ۰۸:۴۰ · تازه شدن خودکار</div></div>
  <div class="cf-body"><table><thead><tr><th>سفارش</th><th class="hide-m">ساعت</th><th>میز</th><th>قلم</th><th class="n">تعداد</th><th class="hide-m">توضیح</th><th>وضعیت</th><th class="n">دقیقه</th></tr></thead><tbody id="cfBq"></tbody></table></div>
</div>
@elseif($n === 3)
<div class="cf" id="cf3">
  <div class="cf-bar"><div class="cf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cf-t">نوشیدنی با اندازه‌ها</div><div class="cf-r">یک بار نام و سه قیمت</div></div>
  <div class="cf-body cf-sz">
    <div class="cf-form">
      <div class="cf-fld"><span>نام نوشیدنی</span><span class="cf-inp" id="cfF0"></span></div>
      <div class="cf-fld"><span>قیمت کوچک</span><span class="cf-inp" id="cfF1"></span></div>
      <div class="cf-fld"><span>قیمت متوسط</span><span class="cf-inp" id="cfF2"></span></div>
      <div class="cf-fld"><span>قیمت بزرگ</span><span class="cf-inp" id="cfF3"></span></div>
      <div><span class="cf-btn is-p" id="cfFs">ساختن سه قلم</span></div>
    </div>
    <div class="cf-cups" id="cfCups"></div>
  </div>
</div>
@elseif($n === 4)
<div class="cf" id="cf4">
  <div class="cf-bar"><div class="cf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cf-t">منوی QR</div><div class="cf-r">با میز یا بدون میز</div></div>
  <div class="cf-body">
    <div class="cf-modes" id="cfModes"><span>کافهٔ بدون میز</span><span>کافهٔ با میز</span></div>
    <div class="cf-qr">
      <div class="cf-qrcard"><span id="cfQt">پیشخوان</span><svg viewBox="0 0 21 21" id="cfQr" shape-rendering="crispEdges" aria-hidden="true"></svg><span id="cfHint">منو را ببینید</span></div>
      <div class="cf-phone"><div class="cf-ph-top"><span>کافه نمونه</span><span id="cfPt">منو</span></div><div class="cf-ph-h">نوشیدنی گرم</div><div class="cf-ph-b" id="cfPb"></div></div>
      <div class="cf-note" id="cfNt"></div>
    </div>
  </div>
</div>
@elseif($n === 5)
<div class="cf" id="cf5">
  <div class="cf-bar"><div class="cf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cf-t">بستن صندوق و شیفت</div><div class="cf-r">کاربر: سارا · امروز · مبالغ به ریال</div></div>
  <div class="cf-body cf-cls">
    <div class="cf-sum" id="cfSb"></div>
    <div class="cf-cnt">
      <div class="cf-fld"><span>نقد مورد انتظار صندوق</span><b class="num" id="cfEx">۰</b></div>
      <div class="cf-fld"><span>نقد شمارش‌شدهٔ صندوق</span><span class="cf-inp" id="cfCt"></span></div>
      <div class="cf-fld"><span>اختلاف</span><span class="cf-diff num" id="cfDf">—</span></div>
      <div><span class="cf-btn is-p" id="cfOk">تأیید صندوق</span></div>
      <div class="cf-lock" id="cfLk">پس از تأیید، فروش و ابطال تازه برای این کاربر در امروز بسته شد.</div>
    </div>
  </div>
  <div class="cf-toast" id="cfT5"></div>
</div>
@elseif($n === 6)
<div class="cf" id="cf6">
  <div class="cf-bar"><div class="cf-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cf-t">درصد بهای نوشیدنی و غذا</div><div class="cf-r">مهر ۱۴۰۵ · حد نمونه: ۳۲٪</div></div>
  <div class="cf-body">
    <table class="cf-cp"><thead><tr><th>نوع</th><th>قلم</th><th class="n hide-m">تعداد</th><th class="n hide-m">فروش خالص</th><th class="n hide-m">بهای مواد</th><th>درصد بها</th></tr></thead><tbody id="cfCpb"></tbody></table>
    <div class="cf-ai" id="cfAi"><div class="av">✦</div><div><b>دستیار هوشمند:</b> درصد بهای «موکا (بزرگ)» این ماه به ۳۸٪ رسید و از حد نمونهٔ ۳۲٪ بالاتر است. نرخ منو یا دستور مصرف آن را بازبینی کنید.</div></div>
  </div>
</div>
@endif
