@if(($part ?? '') === 'board')
<div class="tn" id="tnD1">
  <div class="tn-win">
    <div class="tn-bar"><i></i><i></i><i></i><span>حسابداری تعمیرکاران — پیشخوان تعمیرگاه</span><em>نمونه</em></div>
    <div class="tn-tiles">
      <div><small>کارهای باز</small><b class="num" id="tnK1">۰</b></div>
      <div class="g"><small>آماده تحویل</small><b class="num" id="tnK2">۰</b></div>
      <div><small>دریافتی امروز (ریال)</small><b class="num" id="tnK3">۰</b></div>
      <div class="a"><small>کم‌موجود</small><b class="num" id="tnK4">۰</b></div>
    </div>
    <table class="tn-table"><thead><tr><th>شماره</th><th>مشتری</th><th>کالا</th><th>وضعیت</th></tr></thead><tbody id="tnT1"></tbody></table>
  </div>
</div>
@endif

@if(($part ?? '') === 'intake')
<div class="tn" id="tnD2">
  <h3>پذیرش دستگاه با پیشنهاد هوشمند</h3>
  <p>با موبایل، مشتری و ماندهٔ حسابش پیدا می‌شود. پیشنهاد اجرت فقط از تعمیرهای واقعی قبلی همین تعمیرگاه است.</p>
  <div class="tn-two">
    <div class="tn-win">
      <div class="tn-bar"><i></i><i></i><i></i><span>پذیرش دستگاه</span></div>
      <div class="tn-fld">موبایل مشتری<div class="tn-v" id="tnMob" dir="ltr"></div></div>
      <div class="tn-cust" id="tnCust"><b>آقای نمونه‌پور</b><span>قبض‌های قبلی: <b class="num">۴</b></span><span>مانده: <b class="num">۰</b></span><span>سقف نسیه: <b class="num">۲۰٬۰۰۰٬۰۰۰</b></span></div>
      <div class="tn-grid">
        <div class="tn-fld">کالا<div class="tn-v" id="tnDev"></div></div>
        <div class="tn-fld">برند و مدل<div class="tn-v" id="tnBrand"></div></div>
        <div class="tn-fld">تعمیرکار<div class="tn-v" id="tnTech"></div></div>
      </div>
      <div class="tn-fld">عیب به اظهار مشتری<div class="tn-v" id="tnFault"></div></div>
      <div class="tn-grid">
        <div class="tn-fld">لوازم همراه<div class="tn-v" id="tnAcc"></div></div>
        <div class="tn-fld">بیعانه (ریال)<div class="tn-v num" id="tnDep"></div></div>
        <div class="tn-fld">نوع ایراد<div class="tn-v" id="tnType"></div></div>
      </div>
    </div>
    <div>
      <div class="tn-sug" id="tnSug">
        <h4>پیشنهاد هوشمند — از ۱۲ تعمیر مشابه</h4>
        <div>نوع ایراد پیشنهادی: <b>خرابی آی‌سی شارژ</b></div>
        <div class="tn-range" id="tnRange"><b></b></div>
        <div>بازهٔ اجرت: <b class="num">۱٬۸۰۰٬۰۰۰ تا ۲٬۶۰۰٬۰۰۰</b> ریال</div>
        <div>قطعهٔ پرتکرار: <b>آی‌سی شارژ</b> · میانگین قطعه <b class="num">۳٬۲۰۰٬۰۰۰</b></div>
        <p>فقط از دادهٔ واقعی تعمیرهای قبلی؛ عدد ساختگی نمی‌سازد.</p>
      </div>
      <div class="tn-done" id="tnDone">قبض ثبت شد<div class="tn-big num">قبض ۱۰۸۴ · کد پذیرش ۷۳۱۵</div><span>برچسب قبض چاپ شد</span></div>
    </div>
  </div>
</div>
@endif

@if(($part ?? '') === 'flow')
<div class="tn" id="tnD3">
  <h3>کارتابل ارجاع</h3>
  <div class="tn-lanes">
    <div class="tn-lane" id="tnL0"><h4>پذیرش</h4></div>
    <div class="tn-lane" id="tnL1"><h4>میز آقای رحیمی</h4></div>
    <div class="tn-lane" id="tnL2"><h4>میز آقای کاظمی</h4></div>
  </div>
  <div class="tn-toast" id="tnHt"></div>
</div>
<div class="tn" id="tnD4">
  <h3>تأیید هزینه با کد پیامکی</h3>
  <div class="tn-two">
    <div class="tn-win">
      <div class="tn-bar"><i></i><i></i><i></i><span>تأیید هزینه — قبض ۱۰۸۴</span></div>
      <div class="tn-sum"><span>اجرت</span><b class="num" id="tnA1">—</b></div>
      <div class="tn-sum"><span>قطعه</span><b class="num" id="tnA2">—</b></div>
      <div class="tn-sum"><span>تخفیف</span><b class="num" id="tnA3">—</b></div>
      <div class="tn-sum t"><span>مبلغ قابل تأیید</span><b class="num" id="tnA4">—</b></div>
      <span class="tn-btn" id="tnSend">ثبت درخواست</span>
      <div class="tn-fld">کد تأیید مشتری<div class="tn-v" id="tnCode" dir="ltr"></div></div>
      <div class="tn-ok" id="tnOk">تأیید شد · روش: کد تأیید مشتری · نسخهٔ ۱</div>
    </div>
    <div class="tn-phone"><div>پیامک · گوشی مشتری</div><div class="tn-sms" id="tnSms">تعمیرگاه نمونه: هزینهٔ تعمیر قبض ۱۰۸۴ مبلغ <b class="num">۵٬۴۰۰٬۰۰۰</b> ریال است. کد تأیید: <span dir="ltr">4827</span></div></div>
  </div>
</div>
<div class="tn" id="tnD5">
  <h3>تحویل گروهی و تسویهٔ نقدی</h3>
  <div class="tn-win">
    <div class="tn-bar"><i></i><i></i><i></i><span>تحویل گروهی — جستجو: ۰۹۱۲۰۰۰۰۰۰۰</span></div>
    <table class="tn-table"><thead><tr><th>قبض</th><th>کالا</th><th class="hide-m">قطعه</th><th class="hide-m">اجرت</th><th class="hide-m">بیعانه</th><th>باقی‌مانده</th><th>فاکتور</th></tr></thead><tbody id="tnT5"></tbody></table>
    <div class="tn-pay"><span class="tn-btn" id="tnDl">تحویل و تسویهٔ انتخاب‌شده‌ها · نقدی (صندوق)</span><span>جمع دریافت: <b class="num" id="tnSum">۰</b> ریال</span></div>
  </div>
</div>
@endif

@if(($part ?? '') === 'assistant')
<div class="tn" id="tnD6">
  <div class="tn-two">
    <div class="tn-win"><div class="tn-bar"><i></i><i></i><i></i><span>دستیار هوشمند تعمیرگاه</span></div><div class="tn-chat" id="tnChat"></div></div>
    <div class="tn-win"><div class="tn-bar"><i></i><i></i><i></i><span>حسابرس هوشمند — فقط نشان می‌دهد</span></div>
      <div class="tn-flag" id="tnF1"><b>دستگاه آماده که بیش از ۳۰ روز تحویل نشده</b><p>قبض ۹۸۷ · لپ‌تاپ · آماده از ۴۱ روز پیش · دلیل: مشتری پاسخ نداده</p></div>
      <div class="tn-flag" id="tnF2"><b>تأیید هزینهٔ معطل</b><p>قبض ۱۰۵۲ · مبلغ ۵٬۴۰۰٬۰۰۰ ریال · در انتظار تأیید از ۶ روز پیش</p></div>
      <div class="tn-flag" id="tnF3"><b>قطعهٔ کم‌موجود</b><p>آی‌سی شارژ · موجودی ۲ · نقطهٔ سفارش ۵</p></div>
    </div>
  </div>
</div>
@endif
