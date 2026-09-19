@extends('layouts.app')
@section('title', 'ویرایش کارمند | سرزمین هارد')
@section('page_title', 'ویرایش کارمند و دسترسی‌ها')
@section('window_title', 'ویرایش کارتابل کارمند')

@section('content')
<div class="panel compact-panel">
    <div class="emp-cartable-hero" style="margin-bottom:10px;">
        <div>
            <h2 style="margin:0;font-size:15px;">ویرایش {{ $employee->name }}</h2>
            <p class="lead" style="margin:2px 0 0;">ورود موبایل/SMS و دسترسی طبق وظیفه</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('employees.index') }}">بازگشت به کارتابل</a>
    </div>
    <form method="POST" action="{{ route('employees.update', $employee) }}">
        @csrf @method('PUT')
        @include('employees._form')
        <div class="sms-actions">
            <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
            <a class="btn btn-ghost" href="{{ route('employees.index') }}">انصراف</a>
        </div>
    </form>
    <form method="POST" action="{{ route('employees.welcome-sms', $employee) }}" style="margin-top:8px;">
        @csrf
        <button class="btn btn-secondary" type="submit">ارسال مجدد پیامک خوش‌آمدگویی + لینک ورود</button>
    </form>
</div>
@endsection
