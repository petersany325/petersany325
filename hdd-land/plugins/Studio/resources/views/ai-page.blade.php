<div class="aip">
  <div class="ag-wrap">
    <section class="sec" id="ai1" aria-label="معرفی دستیار">
      <div>
        <h1>دستیار فارسی که <em>روی کامپیوتر خودتان</em> کار می‌کند</h1>
        <p class="sub">می‌پرسید، با صدا یا متن؛ دستیار از دفاتر خود شما جواب می‌دهد، گزارش را توضیح می‌دهد و پیش‌نویس ثبت می‌سازد. ثبت قطعی فقط با تأیید شما.</p>
        <div class="badges">
          <span class="chip g">● بدون اینترنت</span>
          <span class="chip p">🎙️ گفت‌وگوی صوتی فارسی</span>
          <span class="chip p">✓ ثبت فقط با تأیید</span>
        </div>
        <a class="btn" href="{{ url('/contact') }}">درخواست مشاورهٔ رایگان ←</a>
      </div>
      <div class="chat">
        <div class="hd">
          <div class="bot"><img src="{{ asset('images/ai/r-hello.webp') }}" width="64" height="64" alt="دستیار هوشمند HDD Land" fetchpriority="high" decoding="async"></div>
          <div><b>دستیار هوشمند</b><small>● روی همین کامپیوتر · حسابداری فروشگاهی · نمونه</small></div>
        </div>
        <div class="msgs" id="aiC1"></div>
      </div>
    </section>

    <section class="sec" id="ai2">
      <div class="head">
        <div class="bot"><img src="{{ asset('images/ai/r-point.webp') }}" width="118" height="118" alt="" loading="lazy" decoding="async"></div>
        <div class="t">
          <h2>یک پرسش، <em>عامل درست</em> جواب می‌دهد</h2>
          <p class="sub">دستیار مرکزی خودش تشخیص می‌دهد پرسش شما کار حسابدار است، مدیر مالی، تحلیلگر فروش یا تحلیلگر انبار.</p>
        </div>
      </div>
      <div class="ask"><span>🎙️</span><span id="aiQ2"></span><span class="cur" aria-hidden="true"></span></div>
      <div class="hub">↓<b>دستیار مرکزی</b>↓</div>
      <div class="agents" id="aiAg">
        <div class="ag"><div class="ic">🧾</div><b>حسابدار</b><span class="muted">فروش، بدهکاران، چک، بانک، انبار، دفتر کل</span><div class="can chip p">پیشنهاد ثبت با تأیید</div></div>
        <div class="ag"><div class="ic">📊</div><b>مدیر مالی</b><span class="muted">سود و زیان، مقایسهٔ ماه‌ها، بانک، حقوق، ممیزی</span><div class="can chip p">پیشنهاد ثبت با تأیید</div></div>
        <div class="ag"><div class="ic">📈</div><b>تحلیلگر فروش</b><span class="muted">فروش امروز، مقایسهٔ دوره‌ها، مانده‌ها</span><div class="can chip">فقط خواندنی</div></div>
        <div class="ag"><div class="ic">📦</div><b>تحلیلگر انبار</b><span class="muted">کالای رو به اتمام، موجودی، سریال کالا</span><div class="can chip">فقط خواندنی</div></div>
      </div>
    </section>

    <section class="sec" id="ai3">
      <div class="head">
        <div class="bot"><img src="{{ asset('images/ai/r-point.webp') }}" width="118" height="118" alt="" loading="lazy" decoding="async"></div>
        <div class="t">
          <h2>بگویید چه شد؛ <em>پیش‌نویس آماده است</em></h2>
          <p class="sub">حسابدار هوشمند از جملهٔ شما پیشنهاد فاکتور، دریافت، پرداخت یا سند می‌سازد. تا «تأیید» را نزنید، چیزی در دفاتر ثبت نمی‌شود.</p>
        </div>
      </div>
      <div class="grid2">
        <div class="box"><small class="muted">شما · نمونه</small><div class="say3" id="aiT3"></div></div>
        <div class="box draft" id="aiD3">
          <h5><span>پیشنهاد فاکتور فروش</span><span class="chip">پیش‌نویس</span></h5>
          <div class="row"><span>مشتری</span><b>فروشگاه نمونهٔ یاس</b></div>
          <div class="row"><span>کالا</span><b>هارد ۱ ترابایت × ۱</b></div>
          <div class="row"><span>مبلغ</span><b class="num">۱۲۰٬۰۰۰٬۰۰۰ ریال</b></div>
          <div class="row"><span>روش پرداخت</span><b>نسیه</b></div>
          <div class="confirm"><span class="muted">این عملیات ثبت شود؟</span><span class="bt ok" id="aiOk3">تأیید</span><span class="bt">انصراف</span><span class="stamp" id="aiSt3">✓ فاکتور ۴۸۲۲ ثبت شد</span></div>
        </div>
      </div>
    </section>

    <section class="sec" id="ai4">
      <div class="head">
        <div class="bot"><img src="{{ asset('images/ai/r-tablet.webp') }}" width="118" height="118" alt="" loading="lazy" decoding="async"></div>
        <div class="t">
          <h2>عکس فاکتور را بدهید؛ <em>بقیه‌اش با برنامه</em></h2>
          <p class="sub">عکس گوشی، فایل اسکنر، PDF یا Excel فروشنده در یک صف می‌آید. برنامه می‌خواند، فروشنده و کالا را تطبیق می‌دهد، جمع را کنترل می‌کند و تکراری را می‌گیرد. خواندن روی همین کامپیوتر است و تصویر جایی فرستاده نمی‌شود.</p>
        </div>
      </div>
      <div class="queue" id="aiQ4"></div>
    </section>

    <section class="sec" id="ai5">
      <div class="head">
        <div class="bot"><img src="{{ asset('images/ai/r-think.webp') }}" width="118" height="118" alt="" loading="lazy" decoding="async"></div>
        <div class="t">
          <h2>عدد از دفتر می‌آید، <em>نه از حدس</em></h2>
          <p class="sub">نُه بررسی قطعی روی دفاتر، با دلیل برای هر مورد مشکوک. امتیاز سلامت مالی از ۰ تا ۱۰۰ و پیش‌بینی نقد و بانک با بازهٔ اطمینان ۸۰٪. حسابرس فقط نشان می‌دهد و خودش اصلاح نمی‌کند.</p>
        </div>
      </div>
      <div class="grid2">
        <div class="box"><b class="box-t">حسابرس هوشمند · اجرای ۱۲ · نمونه</b><div class="checks" id="aiCk"></div></div>
        <div class="box">
          <div class="health">
            <div class="gauge">
              <svg width="150" height="150" aria-hidden="true"><circle cx="75" cy="75" r="62" fill="none" stroke="#262a4a" stroke-width="12"/><circle id="aiGa" cx="75" cy="75" r="62" fill="none" stroke="#4fa8ff" stroke-width="12" stroke-linecap="round" stroke-dasharray="389.6" stroke-dashoffset="389.6"/></svg>
              <div class="v"><b class="num" id="aiGv">۰</b><small class="muted" id="aiGl">امتیاز سلامت</small></div>
            </div>
            <div class="metrics">
              <div>نسبت جاری <b class="num ok">۱٫۸</b></div>
              <div>ماندگاری نقد <b class="num warn">۴۲ روز</b></div>
              <div>دورهٔ وصول <b class="num">۳۸ روز</b></div>
            </div>
          </div>
          <div class="fc">
            <small class="muted">پیش‌بینی نقد و بانک · ۱۲ هفته · نمونه</small>
            <svg viewBox="0 0 300 120" preserveAspectRatio="none" aria-hidden="true"><line x1="0" y1="96" x2="300" y2="96" stroke="#ff5d6c" stroke-dasharray="4 4" stroke-width="1"/><path id="aiBand" d="" fill="rgba(79,168,255,.18)"/><path id="aiLn" d="" fill="none" stroke="#4fa8ff" stroke-width="2.5"/><circle id="aiRd" r="5" fill="#ff5d6c" opacity="0"/></svg>
            <small id="aiFcn">هفتهٔ ۹: کران پایین زیر صفر؛ علت اصلی: چک پرداختی ۹۰ میلیونی به پخش نمونهٔ البرز</small>
          </div>
        </div>
      </div>
    </section>

    <section class="sec" id="ai6">
      <div class="head">
        <div class="bot"><img src="{{ asset('images/ai/r-thumb.webp') }}" width="118" height="118" alt="" loading="lazy" decoding="async"></div>
        <div class="t">
          <h2>شما تعیین می‌کنید <em>دستیار تا کجا</em> برود</h2>
          <p class="sub">برای هر نقش سطح جدا. هر پرسش با نام کاربر، عامل و زمان پاسخ ثبت می‌شود و داده از کامپیوتر شما بیرون نمی‌رود.</p>
        </div>
      </div>
      <div class="grid2">
        <div class="box">
          <table class="tbl">
            <thead><tr><th>نقش</th><th>سطح دسترسی دستیار</th></tr></thead>
            <tbody>
              <tr><td>مدیر سیستم</td><td><span class="lv full">سطح کامل</span></td></tr>
              <tr><td>حسابدار</td><td><span class="lv ok">اقدام با تأیید</span></td></tr>
              <tr><td>کارمند فروش</td><td><span class="lv dr">پیش‌نویس پیشنهاد</span></td></tr>
              <tr><td>صندوق‌دار</td><td><span class="lv ro">فقط خواندنی</span></td></tr>
              <tr><td>انباردار</td><td><span class="lv no">بدون دسترسی</span></td></tr>
            </tbody>
          </table>
        </div>
        <div class="box"><b class="box-t">تاریخچهٔ درخواست‌ها · نمونه</b><div class="log" id="aiLg"></div></div>
      </div>
      <div class="lock">
        <span class="chip g">🔒 بدون ارسال داده به اینترنت</span>
        <span class="chip p">مدل روی همین کامپیوتر (GGUF)</span>
        <span class="chip p">بدون مدل هم با موتور قواعد کار می‌کند</span>
      </div>
    </section>

    <section class="sec" id="ai7">
      <div class="head">
        <div class="bot"><img src="{{ asset('images/ai/r-hello.webp') }}" width="118" height="118" alt="" loading="lazy" decoding="async"></div>
        <div class="t">
          <h2>هر حسابداری، <em>دستیار خودش</em></h2>
          <p class="sub">دستیار فروشگاه، رستوران، پخش، تولیدی و شرکتی هرکدام نمایه و پرسش‌های خودشان را دارند.</p>
        </div>
      </div>
      <div class="eds" id="aiE7"></div>
      <div class="qs" id="aiQs7"></div>
    </section>
  </div>
</div>
@include('studio::ai-page-script')
