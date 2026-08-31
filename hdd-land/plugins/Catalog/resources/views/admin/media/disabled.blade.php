@extends('layouts.admin')
@section('title','کتابخانه غیرفعال')
@section('content')
<div class="panel" style="max-width:560px;margin:2rem auto;text-align:center">
  <h1>کتابخانه فایل غیرفعال است</h1>
  <p class="muted">از تنظیمات ادمین آن را فعال کنید.</p>
  <a class="btn btn-primary" href="{{ url('/admin/media/settings') }}">تنظیمات کتابخانه</a>
</div>
@endsection
