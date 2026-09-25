@extends('accounting::layouts.acc')
@section('title','کدینگ حساب‌ها')
@section('content')
<div class="top">
  <div>
    <h1>کدینگ حسابداری HDD Land</h1>
    <p>گروه → کل → معین. حساب سیستمی حذف نمی‌شود؛ می‌توانید معین اختصاصی اضافه یا غیرفعال کنید.</p>
  </div>
  <div class="actions">
    <a class="btn g" href="{{ url('/admin/accounting/reports/trial') }}">تراز آزمایشی</a>
    <a class="btn g" href="{{ url($showAll ? '/admin/accounting/chart' : '/admin/accounting/chart?all=1') }}">{{ $showAll ? 'فقط فعال‌ها' : 'نمایش غیرفعال' }}</a>
  </div>
</div>

<div class="panel">
  <div class="hd"><strong>افزودن حساب</strong></div>
  <div class="bd">
    <form method="post" action="{{ url('/admin/accounting/chart') }}" class="form">
      @csrf
      <div class="row">
        <label>کد<input name="code" required placeholder="مثلاً 6108"></label>
        <label>عنوان<input name="name" required placeholder="عنوان فارسی"></label>
      </div>
      <div class="row">
        <label>حساب بالاتر
          <select name="parent_id">
            <option value="">— گروه/کل مستقل —</option>
            @foreach($parents as $p)
              <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
            @endforeach
          </select>
        </label>
        <label>نوع
          <select name="type">
            @foreach($types as $k=>$lab)<option value="{{ $k }}">{{ $lab }}</option>@endforeach
          </select>
        </label>
      </div>
      <div class="row">
        <label>سطح
          <select name="level">
            @foreach($levels as $k=>$lab)<option value="{{ $k }}" @selected($k==='moeen')>{{ $lab }}</option>@endforeach
          </select>
        </label>
        <label>ماهیت
          <select name="nature"><option value="debit">بدهکار</option><option value="credit">بستانکار</option></select>
        </label>
      </div>
      <label class="check"><input type="checkbox" name="is_postable" value="1" checked> قابل ثبت در سند</label>
      <button class="btn" type="submit">ثبت حساب</button>
    </form>
  </div>
</div>

<div class="panel">
  <div class="hd"><strong>درخت سرفصل</strong><span>{{ $accounts->count() }} حساب</span></div>
  <div class="bd" style="padding:0">
    <table>
      <thead><tr><th>کد</th><th>عنوان</th><th>سطح</th><th>نوع</th><th>ثبت</th><th></th></tr></thead>
      <tbody>
      @foreach($accounts as $a)
        @php $pad = max(0, strlen((string)$a->code)-1); @endphp
        <tr>
          <td style="font-family:ui-monospace,monospace;direction:ltr;text-align:left">{{ $a->code }}</td>
          <td style="padding-right:{{ 8+$pad*8 }}px">{{ $a->name }} @if(empty($a->is_active))<span class="badge cancelled">غیرفعال</span>@endif</td>
          <td>{{ $levels[$a->level ?? ''] ?? ($a->level ?? '—') }}</td>
          <td>{{ $types[$a->type] ?? $a->type }}</td>
          <td>{{ !empty($a->is_postable) ? 'بله' : '—' }}</td>
          <td>
            <form method="post" action="{{ url('/admin/accounting/chart/'.$a->id) }}" style="display:flex;gap:.35rem;flex-wrap:wrap">
              @csrf
              <input type="hidden" name="_method" value="put">
              <input name="name" value="{{ $a->name }}" style="width:12rem">
              <label class="check" style="margin:0"><input type="checkbox" name="is_active" value="1" @checked(!empty($a->is_active))> فعال</label>
              <button class="btn g" type="submit">ذخیره</button>
            </form>
            <form method="post" action="{{ url('/admin/accounting/chart/'.$a->id.'/delete') }}" onsubmit="return confirm('حذف یا غیرفعال شود؟')">
              @csrf
              <button class="btn g" type="submit">حذف</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
<div class="panel">
  <div class="hd"><strong>حساب پیش‌فرض سند خودکار</strong></div>
  <div class="bd">
    <form method="post" action="{{ url('/admin/accounting/chart/map') }}" class="form">
      @csrf
      <div class="row">
        @foreach(['cash'=>'صندوق','bank'=>'بانک','gateway'=>'درگاه','ar'=>'مشتری','ar_install'=>'اقساط','inventory'=>'موجودی','sales'=>'فروش کالا','recovery'=>'ریکاوری','repair'=>'تعمیر','cogs'=>'بهای کالا'] as $k=>$lab)
          <label>{{ $lab }}<input name="map_{{ $k }}" value="{{ \Plugins\Accounting\src\Support\AccChart::setting($k) }}"></label>
        @endforeach
      </div>
      <button class="btn" type="submit">ذخیره نگاشت</button>
    </form>
  </div>
</div>
@endsection
