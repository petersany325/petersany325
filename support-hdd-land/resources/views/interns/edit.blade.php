@extends('layouts.app')
@section('title', 'ویرایش کارآموز | سرزمین هارد')
@section('page_title', 'ویرایش کارآموز')
@section('window_title', 'ویرایش دوره کارآموزی')

@section('content')
<div class="panel compact-panel">
    <div class="emp-cartable-hero" style="margin-bottom:10px;">
        <div>
            <h2 style="margin:0;font-size:15px;">ویرایش {{ $intern->name }}</h2>
            <p class="lead" style="margin:2px 0 0;">دوره: {{ $intern->start_date?->format('Y/m/d') }} تا {{ $intern->end_date?->format('Y/m/d') }}</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('interns.index') }}">بازگشت</a>
    </div>
    <form method="POST" action="{{ route('interns.update', $intern) }}">
        @csrf @method('PUT')
        @include('interns._form')
        <div class="sms-actions">
            <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
            <a class="btn btn-ghost" href="{{ route('interns.index') }}">انصراف</a>
        </div>
    </form>
    <form method="POST" action="{{ route('interns.welcome-sms', $intern) }}" style="margin-top:8px;">
        @csrf
        <button class="btn btn-secondary" type="submit">ارسال مجدد پیامک خوش‌آمدگویی</button>
    </form>
</div>
@endsection
