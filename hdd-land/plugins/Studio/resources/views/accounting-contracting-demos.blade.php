@if($n === 1)
<div class="ct" id="ct1">
  <div class="ct-bar"><div class="ct-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="ct-t">جدول کار در جریان</div><div class="ct-r">مبالغ به میلیون ریال</div></div>
  <div class="ct-body ct-scroll">
    <table><thead><tr><th>پروژه</th><th class="n hide-m">مبلغ قرارداد</th><th class="n hide-m">کل بهای برآوردی</th><th class="n">بهای انجام‌شده</th><th>درصد پیشرفت</th><th class="n">درآمد شناسایی‌شده</th><th class="n hide-m">صورت وضعیت انباشته</th><th class="n">دارایی قرارداد</th><th class="n">بدهی قرارداد</th><th class="n">ذخیرهٔ زیان</th></tr></thead><tbody id="ctWip"></tbody></table>
    <div class="muted ct-note">درصد پیشرفت = بهای انجام‌شده ÷ کل بهای برآوردی. صورت وضعیتِ بیش از درآمد «بدهی قرارداد» و کمتر از آن «دارایی قرارداد» است.</div>
  </div>
</div>
@elseif($n === 2)
<div class="ct" id="ct2">
  <div class="ct-bar"><div class="ct-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="ct-t">پروژه‌ها</div><div class="ct-r">هر پروژه روی یک قرارداد · مبالغ به میلیون ریال</div></div>
  <div class="ct-body"><div class="ct-cards" id="ctPcs"></div></div>
</div>
@elseif($n === 3)
<div class="ct" id="ct3">
  <div class="ct-bar"><div class="ct-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="ct-t">صورت وضعیت · پروژهٔ ساختمان اداری</div><div class="ct-r">دورهٔ مهر ۱۴۰۵ · میلیون ریال</div></div>
  <div class="ct-body">
    <div class="ct-tabs" id="ctTabs"><span>کارفرما</span><span>پیمانکار جزء</span></div>
    <div class="ct-st">
      <div class="ct-box" id="ctLines"></div>
      <div class="ct-box"><div class="ct-stt">وضعیت: <span id="ctSst" class="ct-chip is-mute">پیش‌نویس</span></div><div class="ct-vc" id="ctVc"></div></div>
    </div>
  </div>
  <div class="ct-toast" id="ctT3"></div>
</div>
@elseif($n === 4)
<div class="ct" id="ct4">
  <div class="ct-bar"><div class="ct-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="ct-t">ماندهٔ کسور · پروژهٔ ساختمان اداری</div><div class="ct-r">میلیون ریال</div></div>
  <div class="ct-body">
    <div class="ct-ret">
      <div class="ct-rc">حسن انجام کار نزد کارفرما<div class="v num" id="ctR1">۰</div></div>
      <div class="ct-rc">بیمه نزد کارفرما (تا مفاصا)<div class="v num" id="ctR2">۰</div></div>
      <div class="ct-rc is-side">حسن انجام کار پیمانکاران جزء نزد ما<div class="v num" id="ctR3">۰</div></div>
      <div class="ct-rc is-side">بیمهٔ پیمانکاران جزء نزد ما<div class="v num" id="ctR4">۰</div></div>
    </div>
    <div class="ct-rel">
      <div class="ct-box"><b>آزادسازی حسن انجام کار</b><div class="ct-fld"><span>پس از تحویل</span><span class="ct-inp" id="ctA1"></span></div><div><span class="ct-btn is-p" id="ctAb1">آزادسازی</span></div><div class="ct-err" id="ctE1"></div></div>
      <div class="ct-box"><b>آزادسازی بیمه</b><div class="ct-fld"><span>مبلغ</span><span class="ct-inp" id="ctA2"></span></div><div class="ct-fld"><span>شمارهٔ مفاصاحساب</span><span class="ct-inp" id="ctA3"></span></div><div><span class="ct-btn is-p" id="ctAb2">آزادسازی</span></div><div class="ct-err" id="ctE2"></div></div>
    </div>
  </div>
  <div class="ct-toast" id="ctT4"></div>
</div>
@elseif($n === 5)
<div class="ct" id="ct5">
  <div class="ct-bar"><div class="ct-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="ct-t">بها در برابر بودجه · پروژهٔ ساختمان اداری</div><div class="ct-r">میلیون ریال</div></div>
  <div class="ct-body"><div class="ct-bud" id="ctBud"></div>
    <div class="muted ct-note">خط عمودی = بودجهٔ هر سرفصل. صورت وضعیت پیمانکار جزء خودکار زیر سرفصل «پیمانکار جزء» می‌آید.</div>
  </div>
</div>
@elseif($n === 6)
<div class="ct" id="ct6">
  <div class="ct-bar"><div class="ct-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="ct-t">اجرای درصد پیشرفت…</div><div class="ct-r">مثال راهنمای نرم‌افزار · میلیارد ریال</div></div>
  <div class="ct-body ct-poc">
    <div class="ct-box">
      <div class="ct-ln is-on"><span>مبلغ پیمان</span><b class="num">۱۰</b></div>
      <div class="ct-ln is-on"><span>برآورد کل بها</span><b class="num">۸</b></div>
      <div class="ct-ln is-on"><span>بهای انجام‌شده تا امروز</span><b class="num" id="ctX3">۰</b></div>
      <div class="ct-gauge"><i id="ctGi"></i></div>
      <div class="ct-ln is-on"><span>درصد پیشرفت</span><b class="num" id="ctX4">۰٪</b></div>
      <div class="ct-ln is-on"><span>صورت وضعیت تأییدشده</span><b class="num">۴٫۶</b></div>
    </div>
    <div class="ct-dlg" id="ctDlg">
      <b>پیش‌نمایش</b>
      <div class="ct-ln is-on"><span>پیشرفت</span><b class="num">۵۰٪</b></div>
      <div class="ct-ln is-on"><span>درآمد تجمعی</span><b class="num">۵</b></div>
      <div class="ct-ln is-on"><span>درآمد این دوره</span><b class="num">۱٫۵</b></div>
      <div class="ct-ln is-on"><span>دارایی قرارداد</span><b class="num is-pos">۰٫۴</b></div>
      <div class="ct-ln is-on"><span>بدهی قرارداد</span><b class="num muted">—</b></div>
      <div class="ct-q">سند ثبت شود؟ <span class="ct-btn is-p" id="ctYes">بله</span><span class="ct-btn">خیر</span></div>
    </div>
  </div>
  <div class="ct-toast" id="ctT6"></div>
</div>
@endif
