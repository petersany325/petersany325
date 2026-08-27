@extends('layouts.install')

@section('title', 'نصب سایت وکالت')

@section('content')
  <div class="card">
    <h1>نصب سایت وکالت</h1>
    <p class="lead">فقط مشخصات دیتابیس را وارد کنید. حساب ادمین به‌صورت خودکار ساخته می‌شود.</p>

    @if (session('error'))
      <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <h2 style="font-size:1.1rem;margin:1.5rem 0 .75rem">پیش‌نیازها</h2>
    <ul class="req">
      @foreach ($requirements as $label => $ok)
        <li>
          <span>{{ $label }}</span>
          <span class="{{ $ok ? 'ok' : 'bad' }}">{{ $ok ? 'OK' : 'نیازمند رفع' }}</span>
        </li>
      @endforeach
    </ul>

    @if (! $ready)
      <div class="alert alert-error">لطفاً پیش‌نیازها را برطرف کنید و صفحه را رفرش کنید.</div>
    @else
      <form method="post" action="{{ route('install.store') }}">
        @csrf
        <div class="row">
          <label>هاست دیتابیس
            <input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" required>
          </label>
          <label>پورت
            <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required>
          </label>
        </div>
        <label>نام دیتابیس
          <input type="text" name="db_database" value="{{ old('db_database') }}" required placeholder="مثلاً ehamdogf_law">
        </label>
        <div class="row">
          <label>نام کاربری
            <input type="text" name="db_username" value="{{ old('db_username') }}" required>
          </label>
          <label>رمز عبور
            <input type="password" name="db_password" value="{{ old('db_password') }}">
          </label>
        </div>
        @if ($errors->any())
          <div class="alert alert-error">
            <ul style="margin:0;padding-right:1.1rem">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <button class="btn" type="submit">نصب و ساخت ادمین</button>
      </form>
    @endif
  </div>
@endsection
