@extends('layouts.app')
@section('title', 'تعمیرکار جدید | سرزمین هارد')
@section('page_title', 'ثبت تعمیرکار')
@section('content')
<div class="panel" style="max-width:860px;">
    <h2>ثبت تعمیرکار</h2>
    <form method="POST" action="{{ route('technicians.store') }}">
        @csrf
        <div class="form-grid">
            <div><label>نام</label><input type="text" name="name" value="{{ old('name') }}" required></div>
            <div><label>تلفن</label><input type="text" name="phone" value="{{ old('phone') }}"></div>
            <div><label>تخصص</label><input type="text" name="specialty" value="{{ old('specialty') }}" placeholder="هارد، لپ‌تاپ، دیتا ریکاوری..."></div>
            <div><label>کمیسیون %</label><input type="number" name="commission_percent" min="0" max="100" value="{{ old('commission_percent', 0) }}"></div>
            <div>
                @include('partials.toggle', [
                    'name' => 'create_login',
                    'label' => 'ایجاد حساب ورود به سیستم',
                    'checked' => (bool) old('create_login'),
                    'on' => 'روشن',
                    'off' => 'خاموش',
                ])
            </div>
            <div><label>ایمیل ورود</label><input type="email" name="email" value="{{ old('email') }}"></div>
            <div><label>رمز عبور</label><input type="password" name="password"></div>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit">ذخیره</button></div>
    </form>
</div>
@endsection
