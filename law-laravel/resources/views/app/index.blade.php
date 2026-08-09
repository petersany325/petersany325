@extends('layouts.app')

@section('content')
  <section class="hero app-hero">
    <div class="hero-media" aria-hidden="true">
      <img src="{{ asset('assets/img/hero.jpg') }}" alt="" width="1920" height="1080" />
      <div class="hero-overlay"></div>
    </div>
    <div class="hero-content">
      <p class="hero-brand">
        <span>{{ $settings['site_tagline'] ?: 'وکالت · مشاوره · دفاع' }}</span>
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="#appointment">{{ $siteSettings['cta_text'] ?? 'درخواست نوبت' }}</a>
        <a class="btn btn-ghost" href="#services">خدمات</a>
      </div>
    </div>
  </section>

  <section class="section services" id="services">
    <div class="container">
      <div class="section-head">
        <h2>حوزه‌های تخصصی</h2>
        <p>خدمات حقوقی متمرکز و حرفه‌ای.</p>
      </div>
      <ul class="services-list">
        @foreach ($services as $index => $service)
          <li class="service-item">
            <span class="service-num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <div>
              <h3>{{ $service->title }}</h3>
              <p>{{ $service->description }}</p>
            </div>
          </li>
        @endforeach
      </ul>
    </div>
  </section>

  <section class="section about" id="about">
    <div class="container about-grid">
      <div class="about-visual">
        <img src="{{ asset('assets/img/office.jpg') }}" alt="دفتر {{ $settings['site_name'] }}" width="1200" height="800" />
      </div>
      <div class="about-copy">
        <h2>{{ $settings['about_title'] }}</h2>
        <p>{{ $settings['about_text'] }}</p>
      </div>
    </div>
  </section>

  <section class="section" id="team">
    <div class="container">
      <div class="section-head">
        <h2>تیم حقوقی</h2>
      </div>
      <div class="grid-3">
        @foreach ($members as $member)
          <article class="plain-block">
            <h3>{{ $member->name }}</h3>
            <p class="meta">{{ $member->role }}</p>
            <p>{{ \Illuminate\Support\Str::limit($member->bio, 110) }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="section" id="appointment">
    <div class="container">
      <div class="section-head">
        <h2>رزرو نوبت</h2>
        <p>زمان پیشنهادی را بفرستید.</p>
      </div>
      <form class="contact-form" action="{{ route('appointments.store') }}" method="post">
        @csrf
        <input type="hidden" name="from_app" value="1" />
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <div class="hp-field" aria-hidden="true"><label>وب‌سایت<input name="company_website_url" tabindex="-1" autocomplete="new-password"></label></div>
        <div class="form-row">
          <label>نام<input name="name" required value="{{ old('name') }}"></label>
          <label>تلفن<input name="phone" required value="{{ old('phone') }}"></label>
        </div>
        <div class="form-row">
          <label>موضوع<input name="topic" value="{{ old('topic') }}"></label>
          <label>تاریخ<input type="date" name="preferred_date" value="{{ old('preferred_date') }}"></label>
        </div>
        <label>توضیح<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
        <button class="btn btn-primary" type="submit">ثبت نوبت</button>
      </form>
    </div>
  </section>

  <section class="section contact" id="contact">
    <div class="container">
      <div class="section-head">
        <h2>تماس سریع</h2>
      </div>
      <div class="app-quick-contact">
        @if (!empty($settings['phone']))
          <a class="btn btn-primary" href="tel:{{ preg_replace('/\s+/', '', $settings['phone']) }}">تماس: {{ $settings['phone'] }}</a>
        @endif
        @if (!empty($siteSettings['social_whatsapp']))
          <a class="btn btn-ghost app-wa" href="{{ $siteSettings['social_whatsapp'] }}" target="_blank" rel="noopener">واتساپ</a>
        @endif
      </div>
      <form class="contact-form" action="{{ route('contact.store') }}" method="post" style="margin-top:1.25rem">
        @csrf
        <input type="hidden" name="from_app" value="1" />
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <div class="hp-field" aria-hidden="true"><label>وب‌سایت<input name="company_website_url" tabindex="-1" autocomplete="new-password"></label></div>
        <div class="form-row">
          <label>نام<input name="name" required value="{{ old('name') }}"></label>
          <label>تلفن<input name="phone" required value="{{ old('phone') }}"></label>
        </div>
        <label>موضوع
          <select name="topic">
            @foreach (['حقوق خانواده','کیفری','قرارداد و تجارت','ملک و ثبت','سایر'] as $opt)
              <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
          </select>
        </label>
        <label>پیام<textarea name="message" rows="3">{{ old('message') }}</textarea></label>
        <button class="btn btn-primary" type="submit">ارسال</button>
      </form>
      <p class="app-desktop-link"><a href="{{ url('/?desktop=1') }}">نسخه کامل سایت</a></p>
    </div>
  </section>
@endsection
