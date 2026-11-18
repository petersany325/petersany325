<section class="ag-wrap hp" id="hpProducts" aria-labelledby="hpH">
  <header class="hp-head">
    <div>
      <h2 id="hpH">محصولات ما</h2>
      <p>نرم‌افزارهایی که خودمان ساخته‌ایم و هر روز در کسب‌وکارها کار می‌کنند</p>
    </div>
    <a href="{{ url('/projects') }}">همهٔ محصولات ←</a>
  </header>
  <div class="hp-grid">
    <a class="hp-prod" href="{{ url('/computer-accounting') }}">
      <div class="hp-shot"><div class="hp-win">
        <div class="hp-tb"><b>حسابداری HDD Land</b> — <span id="hpEdn">فروشگاهی</span> — سال مالی ۱۴۰۵</div>
        <div class="hp-bd">
          <div class="hp-sd" id="hpSd"></div>
          <div class="hp-mn">
            <div class="hp-eds" id="hpEds"></div>
            <div class="hp-kp">
              <div><small>فروش امروز</small><b class="num" id="hpK1">۰</b></div>
              <div><small>بدهکاران</small><b class="num" id="hpK2">۰</b></div>
              <div><small>موجودی کالا</small><b class="num" id="hpK3">۰</b></div>
            </div>
            <div class="hp-bars" id="hpB1"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i class="last"></i></div>
          </div>
        </div>
      </div></div>
      <div class="hp-info"><span class="tag">نرم‌افزار حسابداری</span><h3>حسابداری HDD Land</h3>
        <p>نسخهٔ ویژهٔ هر صنف، هرکدام با منو و منطق صنف خودش؛ از فروشگاه و رستوران تا پخش و تولید.</p>
        <div class="feat"><span>سامانهٔ مؤدیان</span><span>فاکتور ارزی</span><span>شبکه و چندکاربره</span></div>
        <span class="go">مشاهدهٔ نسخه‌ها ←</span></div>
    </a>

    <a class="hp-prod" href="{{ url('/ai') }}">
      <div class="hp-shot"><div class="hp-ai">
        <div class="hp-hd"><img class="hp-av" src="{{ asset('images/hero-robot.webp') }}" width="30" height="30" alt="" decoding="async"><div><b>دستیار هوشمند</b><small>● روی همین کامپیوتر · نمونه</small></div></div>
        <div class="hp-ms" id="hpMs"></div>
      </div></div>
      <div class="hp-info"><span class="tag">هوش مصنوعی</span><h3>دستیار هوشمند فارسی</h3>
        <p>با صدا یا متن می‌پرسید؛ از دفاتر خودتان جواب می‌دهد، بدون اینترنت. ثبت فقط با تأیید شما.</p>
        <div class="feat"><span>گفت‌وگوی صوتی</span><span>حسابرس هوشمند</span><span>خواندن فاکتور</span></div>
        <span class="go">آشنایی با دستیار ←</span></div>
    </a>

    <a class="hp-prod" href="{{ url('/computer-accounting#network') }}">
      <div class="hp-shot"><div class="hp-phone">
        <div class="hp-ph"><span>سفارش ویزیتور</span></div>
        <div class="hp-off" id="hpOff">● بدون اینترنت</div>
        <div id="hpItems" class="hp-items"></div>
        <div class="hp-sum"><span>جمع</span><span class="num" id="hpPs">۰</span></div>
        <div class="hp-send" id="hpSend">ثبت سفارش</div>
      </div></div>
      <div class="hp-info"><span class="tag">برنامهٔ اندروید</span><h3>اپ همراه حسابداری</h3>
        <p>ویزیتور، گارسون و مدیر هرکدام ابزار خودشان را روی گوشی دارند؛ بی‌اینترنت هم سفارش می‌گیرد.</p>
        <div class="feat"><span>ویزیتور و پخش</span><span>گارسون</span><span>داشبورد مدیر</span></div>
        <span class="go">آشنایی با اپ ←</span></div>
    </a>

    <a class="hp-prod" href="{{ url('/repair-shop') }}">
      <div class="hp-shot"><div class="hp-rep">
        <div class="hp-card"><b>پذیرش ۱۰۸۴ · لپ‌تاپ</b><div class="muted">مشتری: آقای نمونه · ایراد: روشن نمی‌شود</div></div>
        <div class="hp-flow" id="hpFl"><span>پذیرش</span><span>بررسی</span><span>تأیید مشتری</span><span>تعمیر</span><span>تحویل</span></div>
        <div class="hp-card hp-row"><span>قطعه: آی‌سی شارژ</span><b class="num">۳٬۲۰۰٬۰۰۰</b></div>
        <div class="hp-sms" id="hpSms">📩 کد تأیید هزینه برای مشتری پیامک شد</div>
      </div></div>
      <div class="hp-info"><span class="tag">نرم‌افزار تعمیرگاه</span><h3>تعمیرگاه HDD Land</h3>
        <p>از پذیرش دستگاه تا تأیید هزینه با کد پیامکی، قطعهٔ مصرفی و تحویل؛ با اقساط و گزارش کارکنان.</p>
        <div class="feat"><span>تأیید پیامکی</span><span>قطعات و انبار</span><span>اقساط</span></div>
        <span class="go">آشنایی با تعمیرگاه ←</span></div>
    </a>
  </div>
</section>
@include('studio::home-products-script')
