@extends('accounting::layouts.acc')
@section('title','تنظیمات حسابداری')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>تنظیمات حسابداری</h1><p>دسته هزینه‌ها و سرفصل حساب‌ها — افزودن / ویرایش / حذف</p></div></div>

<div class="panel"><div class="hd"><strong>دسته هزینه جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.settings.categories.store') }}" class="form">@csrf
  <div class="row">
    <label>نام<input name="name" required></label>
    <label>کد<input name="code"></label>
  </div>
  <button class="btn" type="submit">ثبت دسته</button>
</form>
</div></div>

<div class="panel"><div class="hd"><strong>دسته‌های هزینه</strong></div><div class="bd" style="padding:0">
@forelse($categories as $c)
<form method="post" action="{{ route('admin.accounting.settings.categories.update',$c->id) }}" class="form" style="padding:.75rem;border-bottom:1px solid var(--line)">
  @csrf
  <div class="row">
    <label>نام<input name="name" value="{{ $c->name }}" required></label>
    <label>کد<input name="code" value="{{ $c->code }}"></label>
  </div>
  <div class="actions">
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($c->is_active)> فعال</label>
    <button class="btn" type="submit">ذخیره</button>
    <button class="btn g" type="submit" formaction="{{ route('admin.accounting.settings.categories.delete',$c->id) }}" onclick="return confirm('حذف شود؟')">حذف</button>
  </div>
</form>
@empty
<div class="bd">دسته‌ای نیست.</div>
@endforelse
</div></div>

<div class="panel"><div class="hd"><strong>سرفصل حساب جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.settings.accounts.store') }}" class="form">@csrf
  <div class="row">
    <label>کد<input name="code" required></label>
    <label>نام<input name="name" required></label>
  </div>
  <label>نوع
    <select name="type">
      @foreach(['asset'=>'دارایی','liability'=>'بدهی','equity'=>'حقوق صاحبان','income'=>'درآمد','expense'=>'هزینه'] as $k=>$v)
        <option value="{{ $k }}">{{ $v }}</option>
      @endforeach
    </select>
  </label>
  <button class="btn" type="submit">ثبت حساب</button>
</form>
</div></div>

<div class="panel"><div class="hd"><strong>سرفصل حساب‌ها</strong></div><div class="bd" style="padding:0">
@foreach($accounts as $a)
<form method="post" action="{{ route('admin.accounting.settings.accounts.update',$a->id) }}" class="form" style="padding:.75rem;border-bottom:1px solid var(--line)">
  @csrf
  <div class="row">
    <label>کد<input name="code" value="{{ $a->code }}" required></label>
    <label>نام<input name="name" value="{{ $a->name }}" required></label>
  </div>
  <div class="row">
    <label>نوع
      <select name="type">
        @foreach(['asset'=>'دارایی','liability'=>'بدهی','equity'=>'حقوق صاحبان','income'=>'درآمد','expense'=>'هزینه'] as $k=>$v)
          <option value="{{ $k }}" @selected($a->type===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </label>
    <label class="check" style="align-self:end"><input type="checkbox" name="is_active" value="1" @checked($a->is_active)> فعال</label>
  </div>
  <div class="actions">
    <button class="btn" type="submit">ذخیره</button>
    <button class="btn g" type="submit" formaction="{{ route('admin.accounting.settings.accounts.delete',$a->id) }}" onclick="return confirm('غیرفعال شود؟')">حذف</button>
  </div>
</form>
@endforeach
</div></div>
@endsection
