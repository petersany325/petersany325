@extends('layouts.app')
@section('title', 'کارمند جدید | سرزمین هارد')
@section('page_title', 'ثبت کارمند')
@section('window_title', 'کارمند جدید — ورود موبایل و وظیفه')

@section('content')
<div class="panel compact-panel">
    <div class="emp-cartable-hero" style="margin-bottom:10px;">
        <div>
            <h2 style="margin:0;font-size:15px;">ثبت کارمند جدید</h2>
            <p class="lead" style="margin:2px 0 0;">وظیفه را انتخاب کنید تا دسترسی‌ها خودکار تنظیم شوند. بعد از ثبت، پیامک خوش‌آمدگویی با لینک ورود ارسال می‌شود.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('employees.index') }}">بازگشت به کارتابل</a>
    </div>
    <form method="POST" action="{{ route('employees.store') }}">
        @csrf
        @include('employees._form', ['employee' => null, 'selected' => $defaults])
        <div class="sms-actions">
            <button class="btn btn-primary" type="submit">ذخیره کارمند</button>
            <a class="btn btn-ghost" href="{{ route('employees.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
