@extends('layouts.admin')
@section('title','ویرایش مشتری')
@section('content')
@php
  $provinces = $provinces ?? [];
  $citiesMap = $citiesMap ?? [];
  $banks = $banks ?? [];
  $curProv = old('province', $user->province);
  $curCity = old('city', $user->city);
  $cities = ($curProv && isset($citiesMap[$curProv])) ? $citiesMap[$curProv] : [];
@endphp
<style>
.cu-edit-head{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center;margin-bottom:1rem}
.cu-edit-head h1{margin:0;font-size:1.25rem}
.cu-edit-head p{margin:.35rem 0 0;color:#6b7280;font-size:.88rem}
.cu-grid{display:grid;gap:1rem;grid-template-columns:1.2fr .8fr}
@media(max-width:960px){.cu-grid{grid-template-columns:1fr}}
.cu-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:1rem 1.1rem}
.cu-card h2{margin:0 0 .85rem;font-size:1.02rem}
.cu-form label{display:block;font-size:.82rem;font-weight:700;margin:.55rem 0 .25rem}
.cu-form input,.cu-form select,.cu-form textarea{
  width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#fff
}
.cu-form .g2{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
@media(max-width:700px){.cu-form .g2{grid-template-columns:1fr}}
.cu-form .ltr{direction:ltr;text-align:left}
.cu-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem}
.cu-meta{font-size:.84rem;color:#475569;line-height:1.7}
.cu-meta strong{color:#0f172a}
.cu-chk{display:flex;align-items:center;gap:.45rem;margin-top:.75rem;font-size:.86rem;font-weight:700}
.cu-chk input{width:auto}
</style>

<div class="panel">
  <div class="cu-edit-head">
    <div>
      <h1>ویرایش مشتری</h1>
      <p>{{ $user->name }} — {{ $user->email }}</p>
    </div>
    <div style="display:flex;gap:.45rem;flex-wrap:wrap">
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/customers') }}">بازگشت به لیست</a>
    </div>
  </div>

  <form class="cu-form" method="post" action="{{ url('/admin/customers/'.$user->id) }}" id="customerEditForm">
    @csrf
    @method('PUT')
    <div class="cu-grid">
      <div class="cu-card">
        <h2>اطلاعات حساب</h2>
        <label>نام و نام خانوادگی
          <input name="name" value="{{ old('name', $user->name) }}" required>
        </label>
        <div class="g2">
          <label>ایمیل
            <input class="ltr" type="email" name="email" value="{{ old('email', $user->email) }}" required>
          </label>
          <label>موبایل
            <input class="ltr" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xxxxxxxxx">
          </label>
        </div>
        <div class="g2">
          <label>کد ملی
            <input class="ltr" name="national_id" value="{{ old('national_id', $user->national_id) }}">
          </label>
          <label>رمز عبور جدید
            <input class="ltr" type="password" name="password" placeholder="خالی = بدون تغییر" autocomplete="new-password">
          </label>
        </div>
        <label class="cu-chk">
          <input type="checkbox" name="phone_verified" value="1" @checked(old('phone_verified', (bool)$user->phone_verified_at))>
          موبایل تأیید شده
        </label>

        <h2 style="margin-top:1.25rem">آدرس</h2>
        <div class="g2">
          <label>استان
            <select name="province" id="cuProvince">
              <option value="">انتخاب استان</option>
              @foreach($provinces as $p)
                <option value="{{ $p }}" @selected($curProv === $p)>{{ $p }}</option>
              @endforeach
            </select>
          </label>
          <label>شهر
            <select name="city" id="cuCity">
              <option value="">انتخاب شهر</option>
              @foreach($cities as $c)
                <option value="{{ $c }}" @selected($curCity === $c)>{{ $c }}</option>
              @endforeach
            </select>
          </label>
        </div>
        <div class="g2">
          <label>کد پستی
            <input class="ltr" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}">
          </label>
          <label>تاریخ تولد
            <input type="date" name="birth_date" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}">
          </label>
        </div>
        <label>آدرس
          <textarea name="address" rows="3">{{ old('address', $user->address) }}</textarea>
        </label>
      </div>

      <div>
        <div class="cu-card">
          <h2>خلاصه</h2>
          <div class="cu-meta">
            <div>شناسه: <strong>#{{ $user->id }}</strong></div>
            <div>موجودی کیف پول: <strong>{{ number_format((int)$balance) }}</strong> تومان</div>
            <div>عضویت: <strong>{{ optional($user->created_at)->format('Y/m/d H:i') ?: '—' }}</strong></div>
          </div>
        </div>

        <div class="cu-card" style="margin-top:1rem">
          <h2>اطلاعات بانکی</h2>
          <label>نام صاحب حساب
            <input name="bank_account_holder" value="{{ old('bank_account_holder', $user->bank_account_holder) }}">
          </label>
          <label>نام بانک
            <select name="bank_name">
              <option value="">انتخاب بانک</option>
              @foreach($banks as $b)
                <option value="{{ $b }}" @selected(old('bank_name', $user->bank_name) === $b)>{{ $b }}</option>
              @endforeach
            </select>
          </label>
          <label>شماره کارت
            <input class="ltr" name="bank_card" maxlength="19" value="{{ old('bank_card', $user->bank_card) }}">
          </label>
          <label>شبا
            <input class="ltr" name="bank_iban" maxlength="26" value="{{ old('bank_iban', $user->bank_iban) }}">
          </label>
        </div>

        <div class="cu-card" style="margin-top:1rem">
          <h2>امنیت / ۲FA</h2>
          <label class="cu-chk">
            <input type="checkbox" name="two_factor_enabled" value="1" @checked(old('two_factor_enabled', (bool)$user->two_factor_enabled))>
            فعال بودن ۲FA
          </label>
          <label>روش ۲FA
            <select name="two_factor_method">
              @foreach(['none'=>'خاموش','sms'=>'پیامک','email'=>'ایمیل','authenticator'=>'Authenticator'] as $k=>$lab)
                <option value="{{ $k }}" @selected(old('two_factor_method', $user->two_factor_method ?: 'none') === $k)>{{ $lab }}</option>
              @endforeach
            </select>
          </label>
        </div>
      </div>
    </div>

    <div class="cu-actions">
      <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
      <a class="btn btn-outline" href="{{ url('/admin/customers') }}">انصراف</a>
    </div>
  </form>
</div>

<script>
(function(){
  var map = @json($citiesMap);
  var prov = document.getElementById('cuProvince');
  var city = document.getElementById('cuCity');
  if(!prov || !city) return;
  var selected = @json($curCity);
  function fill(p, keep){
    var list = map[p] || [];
    city.innerHTML = '';
    var o0 = document.createElement('option');
    o0.value = ''; o0.textContent = 'انتخاب شهر';
    city.appendChild(o0);
    list.forEach(function(name){
      var o = document.createElement('option');
      o.value = name; o.textContent = name;
      if(keep && name === selected) o.selected = true;
      city.appendChild(o);
    });
  }
  prov.addEventListener('change', function(){ selected=''; fill(prov.value, false); });
  fill(prov.value, true);
})();
</script>
@endsection
