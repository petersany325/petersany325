<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $settings['site_name'] }} | وکالت و مشاوره حقوقی</title>
  <meta name="description" content="وکالت، مشاوره و دفاع تخصصی — {{ $settings['site_name'] }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
</head>
<body>
  <header class="site-nav" id="nav">
    <div class="container">
      <a href="#top" class="brand">
        <span class="brand-mark">آ</span>
        {{ $settings['site_name'] }}
      </a>
      <button class="nav-toggle" id="navToggle" aria-label="منو" type="button">
        <span></span><span></span><span></span>
      </button>
      <nav class="nav-links" id="navLinks">
        <a href="#services">خدمات</a>
        <a href="#about">درباره ما</a>
        <a href="#approach">روش کار</a>
        <a href="#contact" class="nav-cta">مشاوره رایگان</a>
      </nav>
    </div>
  </header>

  <main id="top">
    <section class="hero">
      <div class="hero-media" aria-hidden="true">
        <img src="{{ asset('assets/img/hero.jpg') }}" alt="" width="1920" height="1080" />
        <div class="hero-overlay"></div>
      </div>
      <div class="hero-content">
        <h1 class="hero-brand">
          <span>{{ $settings['site_tagline'] }}</span>
          {{ $settings['site_name'] }}
        </h1>
        <p class="hero-lead">{{ $settings['hero_lead'] }}</p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="#contact">رزرو مشاوره</a>
          <a class="btn btn-ghost" href="#services">مشاهده خدمات</a>
        </div>
      </div>
    </section>

    <section class="section services" id="services">
      <div class="container">
        <div class="section-head reveal">
          <h2>حوزه‌های تخصصی</h2>
          <p>تمرکز ما روی پرونده‌هایی است که به دقت، تجربه و پیگیری مستمر نیاز دارند.</p>
        </div>
        <ul class="services-list">
          @foreach ($services as $index => $service)
            <li class="service-item reveal">
              <span class="service-num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
              <div>
                <h3>{{ $service->title }}</h3>
                <p>{{ $service->description }}</p>
              </div>
              <span class="service-arrow" aria-hidden="true">←</span>
            </li>
          @endforeach
        </ul>
      </div>
    </section>

    <section class="section about" id="about">
      <div class="container about-grid">
        <div class="about-visual reveal">
          <img src="{{ asset('assets/img/office.jpg') }}" alt="فضای دفتر {{ $settings['site_name'] }}" width="1200" height="800" />
        </div>
        <div class="about-copy reveal">
          <h2>{{ $settings['about_title'] }}</h2>
          <p>{{ $settings['about_text'] }}</p>
          <a class="btn btn-primary" href="#contact">گفت‌وگو با تیم حقوقی</a>
        </div>
      </div>
    </section>

    <section class="section approach" id="approach">
      <div class="container">
        <div class="section-head reveal">
          <h2>روش کار ما</h2>
          <p>سه مرحله ساده تا شروع پرونده — بدون پیچیدگی اضافی.</p>
        </div>
        <div class="approach-steps">
          <article class="approach-step reveal">
            <div class="step-index">۱</div>
            <h3>جلسه اولیه</h3>
            <p>شنیدن دقیق موضوع، بررسی مدارک و تشخیص اولویت‌های حقوقی شما.</p>
          </article>
          <article class="approach-step reveal">
            <div class="step-index">۲</div>
            <h3>استراتژی پرونده</h3>
            <p>طراحی مسیر اقدام، زمان‌بندی و برآورد واقع‌بینانه از هزینه‌ها و نتایج ممکن.</p>
          </article>
          <article class="approach-step reveal">
            <div class="step-index">۳</div>
            <h3>پیگیری و دفاع</h3>
            <p>اجرای برنامه، گزارش منظم وضعیت و حضور مؤثر در مراجع قضایی.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section contact" id="contact">
      <div class="container contact-grid">
        <div class="contact-info reveal">
          <h2>درخواست مشاوره</h2>
          <p>فرم را پر کنید؛ ظرف یک روز کاری با شما تماس می‌گیریم.</p>
          <dl class="contact-meta">
            <div>
              <dt>تلفن</dt>
              <dd>{{ $settings['phone'] }}</dd>
            </div>
            <div>
              <dt>آدرس</dt>
              <dd>{{ $settings['address'] }}</dd>
            </div>
            <div>
              <dt>ساعات پاسخگویی</dt>
              <dd>{{ $settings['hours'] }}</dd>
            </div>
          </dl>
        </div>

        <form class="contact-form reveal" action="{{ route('contact.store') }}" method="post">
          @csrf
          @if (session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
          @endif
          @if ($errors->any())
            <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
          @endif

          <div class="hp-field" aria-hidden="true">
            <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-row">
            <label>
              نام و نام خانوادگی
              <input type="text" name="name" value="{{ old('name') }}" placeholder="مثال: سارا محمدی" required />
            </label>
            <label>
              شماره تماس
              <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="۰۹۱۲..." required />
            </label>
          </div>
          <label>
            موضوع پرونده
            <select name="topic">
              @foreach (['حقوق خانواده','کیفری','قرارداد و تجارت','ملک و ثبت','سایر'] as $opt)
                <option value="{{ $opt }}" @selected(old('topic', 'حقوق خانواده') === $opt)>{{ $opt }}</option>
              @endforeach
            </select>
          </label>
          <label>
            توضیح کوتاه
            <textarea name="message" rows="4" placeholder="خلاصه موضوع را بنویسید...">{{ old('message') }}</textarea>
          </label>
          <button class="btn btn-primary" type="submit">ارسال درخواست</button>
          <p class="form-note">اطلاعات شما محرمانه می‌ماند و فقط برای پیگیری مشاوره استفاده می‌شود.</p>
        </form>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-top">
        <div>
          <div class="footer-brand">{{ $settings['site_name'] }}</div>
          <p>وکالت و مشاوره حقوقی تخصصی</p>
        </div>
        <nav class="footer-links">
          <a href="#services">خدمات</a>
          <a href="#about">درباره</a>
          <a href="#approach">روش کار</a>
          <a href="#contact">تماس</a>
        </nav>
      </div>
      <div class="footer-bottom">
        <span>{{ $settings['site_name'] }}</span>
        <span><a href="{{ route('admin.login') }}" style="opacity:.7">ورود مدیران</a></span>
      </div>
    </div>
  </footer>

  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
