@extends('layouts.app')
@section('title', 'ثبت عکس مرجع حضور | '.shop_name())
@section('page_title', 'ثبت عکس مرجع')
@section('window_title', 'فقط ادمین — عکس اولیه کارمند')

@section('content')
<div class="emp-cartable">
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="emp-cartable-hero">
        <div>
            <h2>عکس مرجع: {{ $employee->name }}</h2>
            <p class="lead">فقط مدیر سیستم می‌تواند عکس اولیه را ثبت یا عوض کند. کارمند خودش حق ثبت عکس مرجع ندارد.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('attendance.manage') }}">بازگشت</a>
    </div>

    @if($profile?->hasReferencePhoto())
        <div class="panel" style="padding:12px;margin-bottom:12px;">
            <p>عکس فعلی ثبت شده در {{ $profile->enrolled_at?->format('Y-m-d H:i') }}</p>
            <img src="{{ route('attendance.reference-photo', $employee) }}" alt="مرجع" style="max-width:240px;border-radius:8px;border:1px solid #ddd;">
        </div>
    @endif

    <form method="POST" action="{{ route('attendance.enroll.store', $employee) }}" enctype="multipart/form-data" class="panel" style="padding:14px;">
        @csrf
        <label>عکس چهره از روبرو (دوربین یا فایل واضح)</label>
        <input type="file" name="photo" accept="image/*" capture="environment" required>
        <div style="margin-top:12px;">
            <button class="btn btn-primary" type="submit">ذخیره عکس مرجع</button>
        </div>
    </form>
</div>
@endsection
