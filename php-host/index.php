<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$flash = take_flash();
$pageTitle = $config['site_name'] . ' | وکالت و مشاوره حقوقی';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="وکالت، مشاوره و دفاع تخصصی — <?= e($config['site_name']) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <header class="site-nav" id="nav">
    <div class="container">
      <a href="#top" class="brand">
        <span class="brand-mark">آ</span>
        <?= e($config['site_name']) ?>
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
        <img src="assets/img/hero.jpg" alt="" width="1920" height="1080" />
        <div class="hero-overlay"></div>
      </div>
      <div class="hero-content">
        <h1 class="hero-brand">
          <span><?= e($config['site_tagline']) ?></span>
          <?= e($config['site_name']) ?>
        </h1>
        <p class="hero-lead">
          همراهی دقیق و حرفه‌ای در پرونده‌های حقوقی، کیفری و تجاری — با زبانی ساده و دفاعی قوی.
        </p>
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
          <li class="service-item reveal">
            <span class="service-num">۰۱</span>
            <div>
              <h3>حقوق خانواده</h3>
              <p>طلاق، حضانت، مهریه، نفقه و توافقات خانوادگی با رویکرد حمایتی و واقع‌بینانه.</p>
            </div>
            <span class="service-arrow" aria-hidden="true">←</span>
          </li>
          <li class="service-item reveal">
            <span class="service-num">۰۲</span>
            <div>
              <h3>کیفری و دفاع</h3>
              <p>دفاع تخصصی در مراحل تحقیقات، دادگاه و تجدیدنظر با تمرکز بر حقوق متهم.</p>
            </div>
            <span class="service-arrow" aria-hidden="true">←</span>
          </li>
          <li class="service-item reveal">
            <span class="service-num">۰۳</span>
            <div>
              <h3>قرارداد و تجارت</h3>
              <p>تنظیم، بررسی و حل اختلاف قراردادهای تجاری، شرکتی و ملکی.</p>
            </div>
            <span class="service-arrow" aria-hidden="true">←</span>
          </li>
          <li class="service-item reveal">
            <span class="service-num">۰۴</span>
            <div>
              <h3>ملک و ثبت</h3>
              <p>دعاوی ملکی، خلع ید، الزام به تنظیم سند و پیگیری امور ثبتی.</p>
            </div>
            <span class="service-arrow" aria-hidden="true">←</span>
          </li>
        </ul>
      </div>
    </section>

    <section class="section about" id="about">
      <div class="container about-grid">
        <div class="about-visual reveal">
          <img src="assets/img/office.jpg" alt="فضای دفتر <?= e($config['site_name']) ?>" width="1200" height="800" />
        </div>
        <div class="about-copy reveal">
          <h2>دفتری که پرونده را تا نتیجه همراهی می‌کند</h2>
          <p>
            آریان ترکیبی از تجربه وکالت و مشاوره شفاف است. از همان جلسه اول، مسیر پرونده، ریسک‌ها و گزینه‌های واقعی را با شما مرور می‌کنیم.
          </p>
          <p>
            هدف ما فقط تنظیم لایحه نیست؛ ساختن یک استراتژی دفاعی منسجم و قابل پیگیری است.
          </p>
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
              <dd><?= e($config['phone']) ?></dd>
            </div>
            <div>
              <dt>آدرس</dt>
              <dd><?= e($config['address']) ?></dd>
            </div>
            <div>
              <dt>ساعات پاسخگویی</dt>
              <dd><?= e($config['hours']) ?></dd>
            </div>
          </dl>
        </div>

        <form class="contact-form reveal" action="contact.php" method="post" novalidate>
          <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
              <?= e($flash['message']) ?>
            </div>
          <?php endif; ?>

          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
          <div class="hp-field" aria-hidden="true">
            <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
          </div>

          <div class="form-row">
            <label>
              نام و نام خانوادگی
              <input type="text" name="name" placeholder="مثال: سارا محمدی" required value="<?= old('name') ?>" />
            </label>
            <label>
              شماره تماس
              <input type="tel" name="phone" placeholder="۰۹۱۲..." required value="<?= old('phone') ?>" />
            </label>
          </div>
          <label>
            موضوع پرونده
            <?php $selectedTopic = $_SESSION['old']['topic'] ?? 'حقوق خانواده'; ?>
            <select name="topic">
              <?php foreach (['حقوق خانواده', 'کیفری', 'قرارداد و تجارت', 'ملک و ثبت', 'سایر'] as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $selectedTopic === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            توضیح کوتاه
            <textarea name="message" rows="4" placeholder="خلاصه موضوع را بنویسید..."><?= old('message') ?></textarea>
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
          <div class="footer-brand"><?= e($config['site_name']) ?></div>
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
        <span>© <?= e(jdate_year()) ?> <?= e($config['site_name']) ?></span>
        <span>نسخه PHP آماده هاست</span>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
