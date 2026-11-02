@extends('layouts.app')
@section('title', 'پروفایل | '.shop_name())
@section('page_title', 'تنظیمات کاربری')
@section('window_title', 'پروفایل')

@section('content')
<div class="split-2">
    <div class="panel">
        <h2>اطلاعات کاربری</h2>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div><label>نام</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div><label>ایمیل</label><input type="email" name="email" value="{{ old('email', $user->email) }}"></div>
                <div><label>موبایل</label><input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xxxxxxxxx" dir="ltr" style="text-align:left;"></div>
            </div>
            <div class="actions"><button class="btn btn-primary" type="submit">ذخیره</button></div>
        </form>
    </div>

    <div class="panel">
        @if($needsPasswordCreate)
            <h2>ایجاد رمز عبور</h2>
            <p class="muted" style="margin-top:0;">
                هنوز رمزی برای ورود ندارید (ورود قبلی با پیامک بوده).
                لطفاً رمز عبور خود را ایجاد کنید.
            </p>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div>
                        <label>رمز جدید</label>
                        <input type="password" name="password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div>
                        <label>تکرار رمز جدید</label>
                        <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">ایجاد رمز</button>
                </div>
            </form>
        @else
            <h2>تغییر رمز عبور</h2>
            <p class="muted" style="margin-top:0;">
                برای تغییر رمز، ابتدا رمز فعلی و سپس رمز جدید را وارد کنید.
            </p>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div>
                        <label>رمز فعلی</label>
                        <input type="password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div>
                        <label>رمز جدید</label>
                        <input type="password" name="password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div>
                        <label>تکرار رمز جدید</label>
                        <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">تغییر رمز</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
