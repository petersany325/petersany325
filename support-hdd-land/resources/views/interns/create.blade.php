@extends('layouts.app')
@section('title', 'کارآموز جدید | سرزمین هارد')
@section('page_title', 'ثبت کارآموز')
@section('window_title', 'ثبت‌نام کارآموز — تأیید دوره با SMS')

@section('content')
<div class="panel compact-panel">
    <div class="emp-cartable-hero" style="margin-bottom:10px;">
        <div>
            <h2 style="margin:0;font-size:15px;">ثبت کارآموز جدید</h2>
            <p class="lead" style="margin:2px 0 0;">پس از ثبت، پیامک تأیید دوره کارآموزی (از تاریخ تا تاریخ) ارسال می‌شود.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('interns.index') }}">بازگشت</a>
    </div>
    <form method="POST" action="{{ route('interns.store') }}">
        @csrf
        @include('interns._form', ['intern' => null])
        <div class="sms-actions">
            <button class="btn btn-primary" type="submit">ذخیره کارآموز</button>
            <a class="btn btn-ghost" href="{{ route('interns.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
