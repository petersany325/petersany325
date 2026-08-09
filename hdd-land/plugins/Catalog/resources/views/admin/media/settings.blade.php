@extends('layouts.admin')
@section('title','تنظیمات کتابخانه فایل')
@section('content')
@php
  $s = $s ?? [];
  $f = fn($k,$d=null) => old($k, $s[$k] ?? $d);
  $on = fn($k) => !empty(old($k, $s[$k] ?? false));
@endphp
<div class="vb-page">
  <div class="row" style="justify-content:space-between;margin-bottom:1rem">
    <div>
      <h1 style="margin:0">تنظیمات دسترسی کتابخانه</h1>
      <p class="muted">فقط ادمین به این بخش دسترسی دارد؛ اینجا مجوز عملیات را کنترل کنید.</p>
    </div>
    <a class="btn btn-outline" href="{{ url('/admin/media') }}">بازگشت به فایل‌منیجر</a>
  </div>

  <form method="post" action="{{ url('/admin/media/settings') }}">@csrf
    <div class="vb-block">
      <div class="vb-block-head"><span>دسترسی‌ها</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">فعال بودن کتابخانه</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="enabled" value="1" @checked($on('enabled'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">ساخت پوشه جدید</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="allow_mkdir" value="1" @checked($on('allow_mkdir'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">آپلود فایل</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="allow_upload" value="1" @checked($on('allow_upload'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">حذف</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="allow_delete" value="1" @checked($on('allow_delete'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">انتقال (Move)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="allow_move" value="1" @checked($on('allow_move'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">کپی (Copy)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="allow_copy" value="1" @checked($on('allow_copy'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">تغییر نام</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="allow_rename" value="1" @checked($on('allow_rename'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">حداکثر حجم آپلود (KB)</span></div><div class="vb-ctrl"><input type="number" name="max_upload_kb" value="{{ $f('max_upload_kb',5120) }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">پسوندهای مجاز</span><span class="vb-desc">با ویرگول</span></div><div class="vb-ctrl"><input type="text" name="allowed_extensions" value="{{ $f('allowed_extensions') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">نام پوشه ریشه</span></div><div class="vb-ctrl"><input type="text" name="root_folder" value="{{ $f('root_folder','media') }}"></div></div>
        <div class="vb-actions"><button class="btn btn-primary" type="submit">ذخیره تنظیمات</button></div>
      </div>
    </div>
  </form>
</div>
@endsection
