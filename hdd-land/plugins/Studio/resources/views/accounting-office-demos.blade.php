@if($n === 1)
<div class="au" id="au1">
  <div class="au-bar"><div class="au-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="au-t">کارتابل</div><div class="au-r">کاربر: مریم احمدی · کارشناس مالی</div></div>
  <div class="au-cart">
    <div class="au-side">
      <div class="au-si is-on"><span>کارتابل من</span><span class="au-badge" id="auB1">۲</span></div>
      <div class="au-si"><span>ارجاعات دریافتی</span><span class="muted num">۵</span></div>
      <div class="au-si"><span>ارجاعات ارسالی</span><span class="muted num">۳</span></div>
      <div class="au-si"><span>تحت نظر</span><span class="muted num">۲</span></div>
      <div class="au-si"><span>نامه‌ها</span><span class="au-badge" id="auB2">۱</span></div>
    </div>
    <div class="au-list" id="auTl"></div>
  </div>
  <div class="au-toast" id="auT1"></div>
</div>
@elseif($n === 2)
<div class="au" id="au2">
  <div class="au-bar"><div class="au-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="au-t">نامه و دبیرخانه</div><div class="au-r">شمارهٔ جدا برای هر نوع نامه</div></div>
  <div class="au-body au-let">
    <div class="au-paper">
      <div class="au-no" id="auLno"></div>
      <div class="meta">نامهٔ داخلی · از: واحد مالی</div>
      <b>موضوع: بستن حساب‌های فصل پاییز</b>
      <div class="au-rcp" id="auRcp"><span class="au-rc" data-i="0">مدیر عامل<b>✓</b></span><span class="au-rc" data-i="1">مدیر انبار<b>✓</b></span><span class="au-rc is-cc" data-i="2">رونوشت: حسابرس داخلی<b>✓</b></span></div>
      <div class="tx">با سلام، لطفاً گزارش موجودی و اسناد فصل تا پایان هفته به واحد مالی برسد.</div>
      <div class="au-acts"><span class="au-btn is-p" id="auSnd">ارسال با شمارهٔ دبیرخانه</span></div>
    </div>
    <div class="au-box au-sec"><b>دبیرخانه · همهٔ نامه‌های سازمان</b><div id="auSec"></div>
      <div class="muted au-note">فقط کاربر دارای مجوز دبیرخانه این فهرست را می‌بیند. شماره پس از ارسال عوض نمی‌شود.</div></div>
  </div>
</div>
@elseif($n === 3)
<div class="au" id="au3">
  <div class="au-bar"><div class="au-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="au-t">امضا و تأیید با پین</div><div class="au-r">INT-1405-0042</div></div>
  <div class="au-body au-sig">
    <div class="au-box">
      <b>بستن حساب‌های فصل پاییز</b>
      <div class="muted">پین شخصی خود را وارد کنید</div>
      <div class="au-pin" id="auPin"><i></i><i></i><i></i><i></i></div>
      <div class="au-center"><span class="au-btn is-p" id="auSb">امضا و تأیید</span></div>
      <div class="au-disc">برای گردش تأیید داخلی سازمان است، نه گواهی امضای الکترونیکی قانونی.</div>
    </div>
    <div class="au-box">
      <b>جدول امضاهای زیر نامه</b>
      <table><thead><tr><th>نام</th><th class="hide-m">سمت</th><th>امضا</th><th>زمان</th></tr></thead><tbody id="auSgt"></tbody></table>
      <div class="au-ev" id="auEv">رویداد امضا: مدیر عامل · ۱۴۰۵/۰۸/۲۷ ۱۰:۴۲ · نامهٔ INT-1405-0042 را تأیید کرد</div>
    </div>
  </div>
</div>
@elseif($n === 4)
<div class="au" id="au4">
  <div class="au-bar"><div class="au-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="au-t">کار: شمارش موجودی انبار ۲</div><div class="au-r">اولویت: <span class="au-chip is-bad">فوری</span></div></div>
  <div class="au-body au-ref">
    <div class="au-box au-meta">
      <div>گیرنده: <span class="au-chip is-blue">نقش · انباردار</span> <span class="muted">همهٔ دارندگان نقش می‌بینند</span></div>
      <div class="au-who"><span class="au-av">ع</span><span class="au-av">ح</span><span class="muted">۲ انباردار</span></div>
      <div>ناظر: <span class="au-chip is-mute">مدیر مالی</span></div>
      <div>مهلت: <b class="num">۱۴۰۵/۰۸/۲۸ ساعت ۱۴:۰۰</b></div>
      <div>پیوند به: <span class="au-chip is-mute">سند انبار ۷۱۲</span></div>
      <div class="au-rem" id="auRem"><span>۲۴ ساعت مانده</span><span>۱ ساعت مانده</span><span class="is-bad">مهلت گذشت</span></div>
    </div>
    <div class="au-box"><b>تاریخچهٔ ارجاعات</b><div class="au-tl" id="auTlh"></div></div>
  </div>
</div>
@elseif($n === 5)
<div class="au" id="au5">
  <div class="au-bar"><div class="au-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="au-t">گزارش عملکرد کارها</div><div class="au-r">مهر ۱۴۰۵</div></div>
  <div class="au-body">
    <div class="au-tabs" id="auPtabs"><span>هر کارمند</span><span>به تفکیک واحد</span></div>
    <div id="auPrs"></div>
    <div class="au-leg"><span><i class="a"></i>به‌موقع</span><span><i class="b"></i>با دیرکرد</span><span><i class="c"></i>باز</span></div>
  </div>
</div>
@elseif($n === 6)
<div class="au" id="au6">
  <div class="au-bar"><div class="au-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="au-t">کارهای معوق</div><div class="au-r" id="auClk">امروز ۱۴۰۵/۰۸/۲۸ · ۰۸:۰۰</div></div>
  <div class="au-body au-scroll">
    <table><thead><tr><th>کار</th><th class="hide-m">اولویت</th><th>دست چه کسی</th><th class="hide-m">مهلت</th><th>یادآوری‌ها</th><th class="n">دیرکرد</th></tr></thead><tbody id="auOdt"></tbody></table>
  </div>
</div>
@endif
