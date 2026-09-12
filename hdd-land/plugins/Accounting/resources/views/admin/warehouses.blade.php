@extends('accounting::layouts.acc')
@section('title','انبارها')
@section('content')
<div class="top"><div><h1>تعریف و مدیریت انبار چندگانه</h1><p>افزودن، ویرایش، حذف/غیرفعال‌سازی انبارها</p></div></div>
<div class="panel"><div class="hd"><strong>انبار جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.warehouses.store') }}" class="form">@csrf
  <div class="row">
    <label>کد<input name="code" required placeholder="MAIN"></label>
    <label>نام<input name="name" required placeholder="انبار مرکزی"></label>
  </div>
  <div class="row">
    <label>شهر<input name="city"></label>
    <label>آدرس<input name="address"></label>
  </div>
  <label class="check"><input type="checkbox" name="is_default" value="1"> انبار پیش‌فرض</label>
  <button class="btn" type="submit">ثبت انبار</button>
</form>
</div></div>
<div class="panel"><div class="hd"><strong>لیست انبارها</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>کد</th><th>نام</th><th>شهر</th><th>وضعیت</th><th>عملیات</th></tr></thead>
  <tbody>
  @foreach($items as $w)
    <tr>
      <td colspan="5" style="padding:0">
        <form method="post" action="{{ route('admin.accounting.warehouses.update.post',$w->id) }}" class="form" style="padding:.75rem;border-bottom:1px solid var(--line)">
          @csrf
          <div class="row">
            <label>کد<input name="code" value="{{ $w->code }}" required></label>
            <label>نام<input name="name" value="{{ $w->name }}" required></label>
          </div>
          <div class="row">
            <label>شهر<input name="city" value="{{ $w->city }}"></label>
            <label>آدرس<input name="address" value="{{ $w->address }}"></label>
          </div>
          <div class="actions">
            <label class="check"><input type="checkbox" name="is_default" value="1" @checked($w->is_default)> پیش‌فرض</label>
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked($w->is_active)> فعال</label>
            <button class="btn" type="submit">ذخیره</button>
            <button class="btn g" type="submit" formaction="{{ route('admin.accounting.warehouses.delete.post',$w->id) }}" onclick="return confirm('حذف/غیرفعال شود؟')">حذف</button>
          </div>
        </form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
