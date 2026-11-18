@if($n === 1)
<div class="sv" id="sv1">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">سودآوری به تفکیک نوع کار</div><div class="sv-r">مهر ۱۴۰۵ · میلیون ریال</div></div>
  <div class="sv-body sv-scroll">
    <table><thead><tr><th>نوع کار</th><th class="hide-m">دسته</th><th class="n">مقدار فروش</th><th class="n">درآمد خالص</th><th class="n hide-m">ساعت استاندارد</th><th class="n hide-m">بهای استاندارد دستمزد</th><th>حاشیه با بهای استاندارد</th></tr></thead><tbody id="svWp"></tbody></table>
    <div class="muted sv-note">ساعت استاندارد = مقدار فروش × زمان استاندارد ÷ ۶۰. حاشیه = درآمد خالص − بهای استاندارد دستمزد. بیشترین درآمد بالاست.</div>
  </div>
</div>
@elseif($n === 2)
<div class="sv" id="sv2">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">قرارداد خدمات ۱۴۰۵-۱۸</div><div class="sv-r">پشتیبانی سالانه · شرکت نمونهٔ ارغوان</div></div>
  <div class="sv-body">
    <div class="sv-tabs" id="svTabs"><span>اطلاعات</span><span>مالی و پرداخت</span><span>اقساط</span><span>پیوست‌ها</span><span>حسابداری</span><span>تاریخچه</span></div>
    <div class="sv-two">
      <div class="sv-box">
        <div class="sv-ln is-on"><span>مبلغ نهایی</span><b class="num">۶۰۰ میلیون ریال</b></div>
        <div class="sv-ln is-on"><span>پیش‌دریافت</span><b class="num">۱۲۰ میلیون ریال</b></div>
        <div class="sv-ln is-on"><span>دورهٔ قرارداد</span><b class="num">۱۴۰۵/۰۱/۰۱ تا ۱۴۰۵/۱۲/۲۹</b></div>
        <div class="sv-ln is-on"><span>وضعیت</span><span id="svCst" class="sv-chip is-mute">پیش‌نویس</span></div>
        <div class="sv-acts"><span class="sv-btn is-p" id="svAct">فعال‌سازی و ثبت سند</span></div>
      </div>
      <div class="sv-box"><table><thead><tr><th>قسط</th><th>سررسید</th><th class="n">مبلغ</th><th>وضعیت</th></tr></thead><tbody id="svIns"></tbody></table></div>
    </div>
  </div>
  <div class="sv-toast" id="svT2"></div>
</div>
@elseif($n === 3)
<div class="sv" id="sv3">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">فاکتور خدمات</div><div class="sv-r">مشتری: شرکت نمونهٔ ارغوان · ریال</div></div>
  <div class="sv-body sv-inv">
    <div class="sv-scroll"><table><thead><tr><th>خدمت</th><th class="n">مقدار</th><th class="hide-m">واحد</th><th class="n">نرخ</th><th class="n">مبلغ</th></tr></thead><tbody id="svIl"></tbody></table>
      <div class="muted sv-note">خدمت موجودی ندارد و از انبار کم نمی‌شود.</div></div>
    <div class="sv-box" id="svIt"></div>
  </div>
</div>
@elseif($n === 4)
<div class="sv" id="sv4">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">بهای مستقیم خدمات · ردیف «نصب و راه‌اندازی شبکه»</div><div class="sv-r">ریال</div></div>
  <div class="sv-body sv-cost">
    <div>
      <div class="sv-ln is-on is-big"><span>درآمد این ردیف</span><b class="num">۸۵٬۰۰۰٬۰۰۰</b></div>
      <div class="sv-ccs" id="svCcs">
        <div class="sv-cc"><span>قطعهٔ مصرفی · سوئیچ و کابل</span><b class="num">۲۸٬۴۰۰٬۰۰۰</b></div>
        <div class="sv-cc"><span>دستمزد تکنسین</span><b class="num">۱۸٬۰۰۰٬۰۰۰</b></div>
        <div class="sv-cc"><span>سایر هزینه · ایاب و ذهاب</span><b class="num">۲٬۶۰۰٬۰۰۰</b></div>
      </div>
    </div>
    <div class="sv-box">
      <div class="muted">سود ناخالص خدمت = درآمد − (قطعه + دستمزد + سایر)</div>
      <div class="sv-stack"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i></div>
      <div class="sv-leg"><span>قطعه</span><span class="b">دستمزد</span><span class="c">سایر</span><span class="d">سود</span></div>
      <div class="sv-ln is-on is-big"><span>سود ناخالص</span><b class="num is-pos" id="svGp">۰</b></div>
      <div class="sv-ln is-on"><span>حاشیه</span><b class="num" id="svGm">۰٪</b></div>
    </div>
  </div>
</div>
@elseif($n === 5)
<div class="sv" id="sv5">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">انواع کار</div><div class="sv-r">زمان استاندارد · نرخ دستمزد استاندارد</div></div>
  <div class="sv-body"><div class="sv-wt" id="svWts"></div></div>
</div>
@elseif($n === 6)
<div class="sv" id="sv6">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">فاکتورهای فروش با سررسید گذشته</div><div class="sv-r">امروز ۱۴۰۵/۰۸/۲۷ · میلیون ریال</div></div>
  <div class="sv-body sv-col">
    <div>
      <table><thead><tr><th>فاکتور</th><th>مشتری</th><th class="n">مانده</th><th class="n">روز گذشته</th></tr></thead><tbody id="svOd"></tbody></table>
      <div class="sv-ai" id="svAi"><div class="av">✦</div><div><b>دستیار هوشمند:</b> فاکتور ۲۳۱ بیش از ۹۰ روز است وصول نشده. پیگیری کنید.</div></div>
    </div>
    <div class="sv-box"><b>دریافت از شرکت نمونهٔ ارغوان</b>
      <div class="sv-ln is-on"><span>مبلغ دریافت</span><b class="num">۹۰</b></div>
      <div class="sv-ln" id="svAl1"><span>تخصیص به فاکتور ۲۳۱</span><b class="num">۶۵</b></div>
      <div class="sv-ln" id="svAl2"><span>تخصیص به فاکتور ۲۵۸</span><b class="num">۲۵</b></div>
      <div class="sv-acts"><span class="sv-btn is-p" id="svAlb">تخصیص دریافت به فاکتورها</span></div>
    </div>
  </div>
</div>
@elseif($n === 7)
<div class="sv" id="sv7">
  <div class="sv-bar"><div class="sv-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="sv-t">پشتیبانی ماهانه · مثال متن صفحه</div><div class="sv-r">ریال</div></div>
  <div class="sv-body">
    <div class="sv-feat">
      <div class="sv-fk" id="svF1"><div class="k">درآمد</div><div class="v num">۵۰٬۰۰۰٬۰۰۰</div><div class="s">۱۰ ساعت × ۵٬۰۰۰٬۰۰۰</div></div>
      <div class="sv-op">−</div>
      <div class="sv-fk" id="svF2"><div class="k">بهای استاندارد دستمزد</div><div class="v num">۱۸٬۰۰۰٬۰۰۰</div><div class="s">۱۰ ساعت × نرخ استاندارد ۱٬۸۰۰٬۰۰۰</div></div>
      <div class="sv-op">=</div>
      <div class="sv-fk is-res" id="svF3"><div class="k">حاشیه</div><div class="v num" id="svFv">۰</div><div class="s">در گزارش «سودآوری به تفکیک نوع کار»</div></div>
    </div>
    <div class="muted sv-note">برای دقت بیشتر، قطعه یا دستمزد واقعی هر کار را مستقیم به ردیف خدمت در فاکتور وصل کنید تا «سود ناخالص خدمات» همان خدمت دقیق شود.</div>
  </div>
</div>
@endif
