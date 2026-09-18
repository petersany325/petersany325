@extends('layouts.admin')
@section('title', 'پرونده '.$row->public_code)
@section('content')
<style>
  .wr-show{display:grid;gap:1rem;max-width:860px}
  .wr-show h1{margin:0 0 .2rem}
  .wr-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .wr-panel h2{margin:0 0 .7rem;font-size:1.02rem}
  .wr-dl{display:grid;gap:.45rem}
  .wr-dl div{display:grid;grid-template-columns:8rem 1fr;gap:.4rem}
  .wr-dl dt{color:#64748b;font-size:.82rem}
  .wr-stack{display:grid;gap:.7rem}
  .wr-stack label{display:grid;gap:.3rem;font-size:.82rem;color:#475569}
  .wr-stack input,.wr-stack textarea,.wr-stack select{border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit}
  .wr-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.8rem}
  .wr-st{display:inline-block;border-radius:999px;padding:.15rem .55rem;background:#eef2f6}
</style>
<div class="wr-show">
  <div>
    <h1>{{ $row->public_code }} <span class="wr-st">{{ $row->statusLabel() }}</span></h1>
    <p><a href="{{ url('/admin/warranty-register') }}">بازگشت به فهرست</a> · <a href="{{ url('/warranty-register/'.$row->public_code) }}" target="_blank" rel="noopener">صفحه پیگیری مشتری</a></p>
  </div>

  <div class="wr-panel">
    <h2>مشخصات متقاضی</h2>
    <dl class="wr-dl">
      <div><dt>نوع</dt><dd>{{ $row->applicantLabel() }}</dd></div>
      <div><dt>نام</dt><dd>{{ $row->org_name }}</dd></div>
      <div><dt>مسئول</dt><dd>{{ $row->contact_name }}</dd></div>
      <div><dt>موبایل</dt><dd dir="ltr">{{ $row->mobile }}</dd></div>
      @if($row->phone)<div><dt>تلفن</dt><dd dir="ltr">{{ $row->phone }}</dd></div>@endif
      @if($row->city)<div><dt>شهر</dt><dd>{{ $row->city }}</dd></div>@endif
      <div><dt>کالا</dt><dd>{{ trim(($row->product_kind ?? '').' '.($row->brand_model ?? '')) ?: '—' }} · {{ $row->qty }} عدد</dd></div>
      @if($row->serials)<div><dt>سریال‌ها</dt><dd style="white-space:pre-line">{{ $row->serials }}</dd></div>@endif
      @if($row->notes)<div><dt>توضیح</dt><dd>{{ $row->notes }}</dd></div>@endif
    </dl>
  </div>

  <div class="wr-panel">
    <h2>بررسی و اعلام هزینه</h2>
    <form class="wr-stack" method="post" action="{{ url('/admin/warranty-register/'.$row->id) }}">
      @csrf
      <label>وضعیت
        <select name="status">
          @foreach($statuses as $k => $lab)
            <option value="{{ $k }}" @selected($row->status === $k)>{{ $lab }}</option>
          @endforeach
        </select>
      </label>
      <label>بسته
        <input name="package_name" value="{{ $row->package_name }}" list="wr-packs">
        <datalist id="wr-packs">
          @foreach($packages as $p)<option value="{{ $p['name'] }}">@endforeach
        </datalist>
      </label>
      <label>مبلغ پیشنهادی (تومان)
        <input name="quote_amount" type="number" min="0" value="{{ $row->quote_amount }}">
      </label>
      <label>مدت (ماه)
        <input name="quote_months" type="number" min="0" max="60" value="{{ $row->quote_months }}">
      </label>
      <label>یادداشت داخلی / قابل نمایش در اعلام هزینه
        <textarea name="admin_note" rows="3">{{ $row->admin_note }}</textarea>
      </label>
      <div class="wr-actions">
        <button class="btn" type="submit">ذخیره و در صورت تغییر وضعیت پیامک</button>
      </div>
    </form>
    @if($row->sms_log)
      <p style="margin:.9rem 0 0;color:#64748b;font-size:.82rem;white-space:pre-line">لاگ پیامک:
{{ $row->sms_log }}</p>
    @endif
  </div>
</div>
@endsection
