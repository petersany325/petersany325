@extends('studio::layout')
@section('title', ($title ?? 'پشتیبانی').' | فراز نت آمل')
@section('content')
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">پشتیبانی فراز نت آمل</p>
    <h1>راه‌های تماس و تیکت پشتیبانی</h1>
    <p class="ag-lead ag-lead--wide">پیام شما تیکت می‌شود و اگر شماره موبایل باشد پیامک تأیید می‌آید.</p>
    @if(session('success'))<div class="ag-note">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ag-note ag-note--err">{{ session('error') }}</div>@endif
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="tel:{{ preg_replace('/\s+/', '', $phone ?? '01144447220') }}">تماس {{ $phone ?? '01144447220' }}<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">صفحه تماس</a>
      <a class="ag-btn ag-btn--line" href="{{ url('/account/tickets') }}">تیکت مشتری</a>
      <a class="ag-btn ag-btn--line" href="{{ url('/staff/desk/tickets') }}">تیکت کارمند</a>
    </div>
    <form class="ag-auth-form" method="post" action="{{ url('/support') }}" style="margin-top:1.2rem">
      @csrf
      <label>نام<input name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required></label>
      <label>موبایل<input name="phone" dir="ltr" value="{{ old('phone') }}" placeholder="0912..."></label>
      <label>موضوع<input name="subject" value="{{ old('subject') }}" required></label>
      <label>پیام<textarea name="body" rows="5" required>{{ old('body') }}</textarea></label>
      <button class="ag-btn ag-btn--on" type="submit">ارسال پیام پشتیبانی</button>
    </form>
    <h2 style="margin-top:1.6rem">سؤالات متداول</h2>
    <ul class="ag-list">
      @foreach(($faq ?? []) as $row)
        <li><b>{{ $row['q'] }}</b> — {{ $row['a'] }}</li>
      @endforeach
    </ul>
  </div>
</section>
@endsection
