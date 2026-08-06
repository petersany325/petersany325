@extends('layouts.admin')
@section('title', 'ورود ادمین')
@section('body')
<div class="login-wrap">
  <div class="card">
    <h2 style="margin-top:0">ورود به پنل مدیریت</h2>
    @if ($errors->any())
      <div class="alert" style="background:rgba(160,50,50,.1);color:#8a2a2a">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('admin.login.submit') }}">
      @csrf
      <label>ایمیل
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
      </label>
      <label>رمز عبور
        <input type="password" name="password" required>
      </label>
      <label style="display:flex;align-items:center;gap:.5rem;font-weight:500">
        <input type="checkbox" name="remember" value="1" style="width:auto"> مرا به خاطر بسپار
      </label>
      <button class="btn" type="submit">ورود</button>
    </form>
  </div>
</div>
@endsection
