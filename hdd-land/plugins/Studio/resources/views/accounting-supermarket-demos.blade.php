@if($n === 1)
<div class="sm" id="sm1">
  <div class="sm-bar"><div class="sm-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sm-t">صندوق فروش · ایستگاه ۱</div><div class="sm-r">شیفت باز · صندوق‌دار: سارا محمدی</div></div>
  <div class="sm-pos">
    <div class="sm-cart">
      <div class="sm-susp" id="smSusp"></div>
      <div class="sm-scan" id="smScan"><span>▮▮▯▮</span><span class="sm-bc" id="smBc">—</span><span class="muted" id="smWho">مشتری نقدی</span></div>
      <table><thead><tr><th>کالا</th><th class="n">مقدار</th><th class="n">مبلغ (ریال)</th></tr></thead><tbody id="smCart"></tbody></table>
    </div>
    <div class="sm-pay">
      <div class="sm-tot">جمع رسید<b class="num" id="smTot">۰</b></div>
      <div class="sm-pf"><span>نقد دریافتی</span><span class="num" id="smCash">۰</span></div>
      <div class="sm-pf"><span>کارت</span><span class="num muted">۰</span></div>
      <div class="sm-pf is-ch"><span>باقی پول</span><span class="num" id="smChg">۰</span></div>
      <div class="sm-keys"><span class="sm-key" id="smF8">F8</span>معلق کن <span class="sm-key" id="smF7">F7</span>معلق‌ها <span class="sm-key" id="smF9">F9</span>ثبت رسید <span class="sm-key" id="smF11">F11</span>کشوی پول</div>
    </div>
  </div>
  <div class="sm-toast" id="smT1"></div>
</div>
@elseif($n === 2)
<div class="sm" id="sm2">
  <div class="sm-bar"><div class="sm-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sm-t">صندوق فروش · ایستگاه ۲</div><div class="sm-r">رسیدهای این شیفت</div></div>
  <div class="sm-banner is-on" id="smBan"><span id="smBant">متصل به سرور</span><span class="sm-rt" id="smRt"></span></div>
  <div id="smRcs"></div>
  <div class="sm-rule"><span>قیمت، تخفیف، مالیات و بارکد ترازو مثل همیشه</span><span>نقد و کارت پذیرفته می‌شود</span><span>تا همهٔ رسیدها ثبت نشده، شیفت بسته نمی‌شود</span></div>
</div>
@elseif($n === 3)
<div class="sm" id="sm3">
  <div class="sm-bar"><div class="sm-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sm-t">نمایشگر مشتری و کشوی پول</div><div class="sm-r">مانیتور دوم · VFD</div></div>
  <div class="sm-disp">
    <div class="sm-mon-wrap">
      <div class="sm-mon">
        <div class="sm-wel" id="smMw">به فروشگاه نمونه خوش آمدید</div>
        <div><div class="sm-it" id="smMi">—</div><div class="sm-pr num" id="smMp"></div></div>
        <div class="sm-sum"><div><div class="sm-ds" id="smMd"></div><div class="sm-chg num" id="smMc"></div></div><div class="sm-sum-r"><div class="muted">جمع</div><b class="num" id="smMs">۰</b></div></div>
      </div>
    </div>
    <div class="sm-side3">
      <div>
        <div class="muted sm-lab">نمایشگر دوخطی پایه‌دار (VFD)</div>
        <div class="sm-vfd"><div id="smV1">WELCOME</div><div id="smV2">TOTAL        0</div></div>
      </div>
      <div class="sm-drawer">کشوی پول<div class="sm-dbox" id="smDbox"><i></i></div><div class="sm-dlab muted" id="smDlab">بسته</div></div>
    </div>
  </div>
</div>
@elseif($n === 4)
<div class="sm" id="sm4">
  <div class="sm-bar"><div class="sm-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sm-t">باشگاه مشتریان · در صندوق</div><div class="sm-r">نمونهٔ تنظیم: هر ۱۰٬۰۰۰ ریال خرید = ۱ امتیاز</div></div>
  <div class="sm-club">
    <div class="sm-card">
      <span class="sm-lvl" id="smLvl">نقره‌ای · ضریب نمونه ۱٫۵</span>
      <div class="sm-nm">مریم احمدی</div><div class="sm-mb num">0912 *** 4471</div>
      <div class="sm-pts">امتیاز<b class="num" id="smPt">۰</b><span id="smPtv">ارزش: ۰ ریال</span></div>
      <div class="sm-prog">تا سطح طلایی (خرید ۱۲ ماه)<div class="sm-tr"><i id="smPg"></i></div></div>
    </div>
    <div class="sm-steps">
      <div class="sm-s4" data-i="0"><span>موبایل مشتری · Enter</span><span class="sm-chip is-ok">پیدا شد</span></div>
      <div class="sm-s4" data-i="1"><span>جمع خرید</span><span class="sm-v num">۱٬۲۵۰٬۰۰۰ ریال</span></div>
      <div class="sm-s4" data-i="2"><span>خرج ۳۰۰ امتیاز</span><span class="sm-v num is-minus">− ۳۰٬۰۰۰ ریال</span></div>
      <div class="sm-s4" data-i="3"><span>امتیاز این خرید (روی مبلغ پرداخت‌شده × ضریب نمونه)</span><span class="sm-v num is-plus">+ ۱۸۳</span></div>
      <div class="sm-acct" id="smAcct">ثبت حسابداری: امتیاز داده‌شده تعهد فروشگاه است و جدا ثبت می‌شود. امتیاز منقضی‌شده با پیامک یادآوری قبلی، تعهد را برمی‌گرداند.</div>
    </div>
  </div>
</div>
@elseif($n === 5)
<div class="sm" id="sm5">
  <div class="sm-bar"><div class="sm-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sm-t">گزارش فروش ساعتی صندوق</div><div class="sm-r">امروز · فروش خالص به میلیون ریال</div></div>
  <div class="sm-body">
    <div class="sm-st" id="smSts"><span>همهٔ ایستگاه‌ها</span><span>ایستگاه ۱</span><span>ایستگاه ۲</span></div>
    <div class="sm-chart" id="smChart"></div>
    <div class="sm-hrs" id="smHrs"></div>
    <div class="sm-peak">
      <div class="sm-pk"><span class="muted">شلوغ‌ترین ساعت</span><b class="num" id="smPkh">—</b></div>
      <div class="sm-pk"><span class="muted">تعداد رسید</span><b class="num" id="smPkr">—</b></div>
      <div class="sm-pk"><span class="muted">میانگین سبد</span><b class="num" id="smPka">—</b></div>
      <div class="sm-pk"><span class="muted">سهم از فروش</span><b class="num" id="smPks">—</b></div>
    </div>
    <div class="muted sm-note">رسیدهای بی‌اتصال در ساعت واقعی فروش حساب می‌شوند. عملکرد صندوق‌داران گزارش جدا دارد.</div>
  </div>
</div>
@endif
