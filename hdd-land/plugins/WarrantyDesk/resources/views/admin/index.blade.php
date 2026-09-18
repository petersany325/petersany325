@extends('layouts.admin')
@section('title', 'ثبت گارانتی')
@section('content')
<style>
  .wr-admin{display:grid;gap:1.1rem}
  .wr-admin h1{margin:0 0 .25rem;font-size:1.25rem}
  .wr-admin .muted{color:#64748b;margin:0}
  .wr-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .wr-panel h2{margin:0 0 .75rem;font-size:1.02rem}
  .wr-formgrid{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  .wr-formgrid label,.wr-stack label{display:grid;gap:.3rem;font-size:.82rem;color:#475569}
  .wr-formgrid input,.wr-formgrid textarea,.wr-formgrid select,.wr-stack input,.wr-stack textarea,.wr-stack select{
    border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#fff
  }
  .wr-stack{display:grid;gap:.7rem}
  .wr-full{grid-column:1/-1}
  .wr-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-top:.8rem}
  .wr-table{width:100%;border-collapse:collapse;font-size:.9rem}
  .wr-table th,.wr-table td{padding:.55rem .45rem;border-bottom:1px solid #eef2f6;text-align:right}
  .wr-table a{font-weight:700}
  .wr-st{display:inline-block;border-radius:999px;padding:.15rem .55rem;font-size:.75rem;background:#eef2f6}
  .wr-check{display:flex;align-items:center;gap:.4rem;font-size:.9rem}
  @media(max-width:800px){.wr-formgrid{grid-template-columns:1fr}}
</style>
<div class="wr-admin">
  <div>
    <h1>ثبت گارانتی سازمانی</h1>
    <p class="muted">متن صفحه عمومی را اینجا ویرایش کنید و درخواست‌های پوشش را بررسی کنید. <a href="{{ url('/warranty-register') }}" target="_blank" rel="noopener">مشاهده صفحه</a></p>
  </div>

  <div class="wr-panel">
    <h2>ویرایش متن صفحه</h2>
    <form class="wr-stack" method="post" action="{{ url('/admin/warranty-register/page') }}">
      @csrf
      <div class="wr-formgrid">
        <label>عنوان کوتاه<input name="kicker" value="{{ $copy['kicker'] }}" maxlength="80"></label>
        <label>عنوان صفحه<input name="title" value="{{ $copy['title'] }}" maxlength="160"></label>
        <label class="wr-full">متن معرفی<textarea name="lead" rows="3" maxlength="600">{{ $copy['lead'] }}</textarea></label>
        <label class="wr-full">توضیح بیشتر<textarea name="intro" rows="3" maxlength="900">{{ $copy['intro'] }}</textarea></label>
        <label>نکات (هر خط یک مورد)<textarea name="bullets" rows="5">{{ $copy['bullets'] }}</textarea></label>
        <label>مراحل (هر خط یک مرحله)<textarea name="steps" rows="5">{{ $copy['steps'] }}</textarea></label>
        <label>عنوان فرم<input name="form_title" value="{{ $copy['form_title'] }}" maxlength="160"></label>
        <label>متن دکمه<input name="cta_label" value="{{ $copy['cta_label'] }}" maxlength="60"></label>
        <label class="wr-full">یادداشت زیر فرم<textarea name="note" rows="2">{{ $copy['note'] }}</textarea></label>
        <label class="wr-full">پیام بعد از ثبت<textarea name="success" rows="2">{{ $copy['success'] }}</textarea></label>
        <label>عنوان پیگیری<input name="lookup_title" value="{{ $copy['lookup_title'] }}"></label>
        <label>راهنمای پیگیری<input name="lookup_hint" value="{{ $copy['lookup_hint'] }}"></label>
        <label class="wr-full">بسته‌ها (نام|ماه|قیمت تومان)<textarea name="packages" rows="4" placeholder="پوشش ۱۲ ماه|12|1500000">{{ $copy['packages'] }}</textarea></label>
      </div>
      <label class="wr-check"><input type="checkbox" name="sms_on_status" value="1" @checked(!empty($copy['sms_on_status']))> پیامک در هر تغییر وضعیت</label>
      <div class="wr-actions">
        <button class="btn" type="submit">ذخیره متن صفحه</button>
        <a class="btn" href="{{ url('/warranty-register') }}" target="_blank" rel="noopener" style="background:#0f172a">باز کردن صفحه</a>
      </div>
    </form>
  </div>

  <div class="wr-panel">
    <h2>درخواست‌های پوشش</h2>
    <form method="get" class="wr-actions" style="margin-top:0">
      <input name="q" value="{{ $q }}" placeholder="کد، نام، موبایل" style="border:1px solid #d0dbe3;border-radius:10px;padding:.5rem .7rem">
      <select name="status" style="border:1px solid #d0dbe3;border-radius:10px;padding:.5rem .7rem">
        <option value="">همه وضعیت‌ها</option>
        @foreach($statuses as $k => $lab)
          <option value="{{ $k }}" @selected($status === $k)>{{ $lab }}</option>
        @endforeach
      </select>
      <button class="btn" type="submit">فیلتر</button>
    </form>
    <div style="overflow:auto;margin-top:.6rem">
      <table class="wr-table">
        <thead>
          <tr><th>کد</th><th>متقاضی</th><th>موبایل</th><th>وضعیت</th><th>تاریخ</th></tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          <tr>
            <td><a href="{{ url('/admin/warranty-register/'.$r->id) }}">{{ $r->public_code }}</a></td>
            <td>{{ $r->org_name }}<br><small>{{ $r->applicantLabel() }} · {{ $r->contact_name }}</small></td>
            <td dir="ltr">{{ $r->mobile }}</td>
            <td><span class="wr-st">{{ $r->statusLabel() }}</span></td>
            <td>{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="5">درخواستی ثبت نشده.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
