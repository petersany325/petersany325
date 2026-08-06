@extends('layouts.admin-app')
@section('title', 'تنظیمات')
@section('heading', 'تنظیمات سایت')
@section('content')
<div class="card">
  <form method="post" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')
    <label>نام برند<input name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required></label>
    <label>شعار<input name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}"></label>
    <label>متن هیرو<textarea name="hero_lead" rows="3">{{ old('hero_lead', $settings['hero_lead']) }}</textarea></label>
    <label>تلفن<input name="phone" value="{{ old('phone', $settings['phone']) }}"></label>
    <label>آدرس<input name="address" value="{{ old('address', $settings['address']) }}"></label>
    <label>ساعات<input name="hours" value="{{ old('hours', $settings['hours']) }}"></label>
    <label>عنوان درباره ما<input name="about_title" value="{{ old('about_title', $settings['about_title']) }}"></label>
    <label>متن درباره ما<textarea name="about_text" rows="4">{{ old('about_text', $settings['about_text']) }}</textarea></label>
    <button class="btn" type="submit">ذخیره تنظیمات</button>
  </form>
</div>
@endsection
