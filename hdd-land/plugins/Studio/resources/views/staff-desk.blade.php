@extends('studio::layout')
@section('title', ($title ?? 'کارتابل نماینده').' | فراز نت آمل')
@section('content')
@php
  $tab = $tab ?? 'home';
  $menu = $menu ?? [];
  $path = '/'.ltrim(request()->path(), '/');
@endphp
<section class="ag-page sd">
  <div class="ag-wrap">
    <p class="ag-kicker">کارتابل نمایندگان شهرستان</p>
    <h1>{{ $title ?? 'میز کار' }}</h1>
    <p class="ag-lead">قیمت فروش، درصد سهم و فاکتور اینجا ثبت می‌شود. پرداخت بانکی وقتی کلید درگاه بیاید فعال است.</p>
    @if(session('success'))<div class="ag-note">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ag-note ag-note--err">{{ session('error') }}</div>@endif
    <p class="sd-bal">سهم ثبت‌شده در حساب شما: <b>{{ number_format((int)($balance ?? 0)) }} تومان</b></p>

    <nav class="sd-nav" aria-label="منوی کارتابل دسکتاپ">
      @foreach($menu as $item)
        @php $on = $path === trim($item['url'], '/') || $path === ltrim($item['url'], '/'); @endphp
        <a class="{{ $on ? 'is-on' : '' }}" href="{{ url($item['url']) }}">{{ $item['label'] }}</a>
      @endforeach
    </nav>

    @if($tab === 'home')
      <div class="ag-cards ag-cards--dense">
        <a class="ag-card" href="{{ url('/staff/desk/buy') }}"><div><b>خرید</b><p>نرم‌افزار حسابداری و بقیه نسخه‌ها را ببینید.</p></div></a>
        <a class="ag-card" href="{{ url('/staff/desk/sell') }}"><div><b>فروش</b><p>فروش بزنید؛ فاکتور و سهم ثبت می‌شود.</p></div></a>
        <a class="ag-card" href="{{ url('/staff/desk/tickets') }}"><div><b>تیکت‌ها</b><p>تیکت باز است و به پیامک وصل است.</p></div></a>
        <a class="ag-card" href="{{ url('/staff/desk/prices') }}"><div><b>قیمت‌های فروش</b><p>قیمت و درصد سهم هر نرم‌افزار.</p></div></a>
      </div>
    @endif

    @if(in_array($tab, ['buy','sell','prices'], true))
      <div class="sd-table-wrap">
        <table class="sd-table">
          <thead><tr><th>نرم‌افزار</th><th>قیمت</th><th>درصد سهم</th><th>لینک</th></tr></thead>
          <tbody>
            @foreach(($offers ?? []) as $row)
              <tr>
                <td>{{ $row['title'] }}</td>
                <td>{{ $row['price_label'] }}</td>
                <td>{{ $row['commission'] }}٪</td>
                <td>
                  <a href="{{ url($row['url']) }}">معرفی</a>
                  @if($tab === 'sell')
                    · <a href="{{ url($row['sell_url']) }}">فروش</a>
                  @elseif($tab === 'buy')
                    · <a href="{{ url($row['sell_url']) }}">خرید / پرداخت</a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if($tab === 'sell')
        <form class="ag-auth-form" method="post" action="{{ url('/staff/desk/sell') }}" style="margin-top:1rem">
          @csrf
          <label>نرم‌افزار
            <select name="sku" required>
              @foreach(($offers ?? []) as $row)
                <option value="{{ $row['sku'] }}">{{ $row['title'] }}</option>
              @endforeach
            </select>
          </label>
          <label>نام خریدار
            <input name="buyer" required maxlength="120">
          </label>
          <label>درگاه
            <select name="gateway">
              <option value="zarinpal">زرین‌پال</option>
              <option value="melli">بانک ملی</option>
            </select>
          </label>
          <button class="ag-btn ag-btn--on" type="submit">ثبت فروش و فاکتور</button>
        </form>
      @endif
    @endif

    @if(in_array($tab, ['sales','invoices'], true))
      <div class="sd-table-wrap">
        <table class="sd-table">
          <thead><tr><th>فاکتور</th><th>محصول</th><th>قیمت</th><th>درصد</th><th>سهم</th><th>درگاه</th></tr></thead>
          <tbody>
            @forelse(($sales ?? []) as $row)
              <tr>
                <td>{{ $row['id'] }}</td>
                <td><a href="{{ url($row['url'] ?? '/') }}">{{ $row['title'] }}</a></td>
                <td>{{ $row['price_label'] ?? '' }}</td>
                <td>{{ $row['commission'] ?? 0 }}٪</td>
                <td>{{ number_format((int)($row['share'] ?? 0)) }}</td>
                <td>{{ $row['gateway'] === 'melli' ? 'بانک ملی' : 'زرین‌پال' }}</td>
              </tr>
            @empty
              <tr><td colspan="6">هنوز فروشی ثبت نشده.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif

    @if($tab === 'reps')
      <div class="sd-table-wrap">
        <table class="sd-table">
          <thead><tr><th>نماینده</th><th>نقش</th><th>درصد پیش‌فرض</th><th>سهم ثبت‌شده</th></tr></thead>
          <tbody>
            @forelse(($reps ?? []) as $row)
              <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['role'] }}</td>
                <td>{{ $row['commission'] }}٪</td>
                <td>{{ number_format((int)$row['balance']) }} تومان</td>
              </tr>
            @empty
              <tr><td colspan="4">لیست نمایندگان از پنل کارمندان خوانده می‌شود. اگر خالی است، کارمند را از ادمین بسازید.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif

    @if($tab === 'tickets')
      <form class="ag-auth-form" method="post" action="{{ url('/staff/desk/tickets') }}">
        @csrf
        <label>موضوع<input name="subject" required maxlength="160"></label>
        <label>متن<textarea name="body" required rows="4"></textarea></label>
        <button class="ag-btn ag-btn--on" type="submit">ثبت تیکت</button>
      </form>
      <ul class="ag-list" style="margin-top:1rem">
        @forelse(($tickets ?? []) as $row)
          <li><b>{{ $row['id'] }}</b> — {{ $row['subject'] }} ({{ $row['status'] }})</li>
        @empty
          <li>تیکتی نیست.</li>
        @endforelse
      </ul>
    @endif

    @if($tab === 'sms')
      <p class="ag-lead">پیامک ورود، تیکت و فاکتور به همین درگاه نیازپرداز وصل است.</p>
      <p>وضعیت درگاه: <b>{{ !empty($sms['ready']) ? 'آماده ارسال' : 'منتظر مشخصات پیامک' }}</b></p>
      <a class="ag-btn ag-btn--line" href="{{ url('/admin/studio-sms') }}">تنظیم پیامک</a>
    @endif
  </div>
</section>

<nav class="sd-tabbar" aria-label="منوی موبایل کارتابل">
  @foreach($menu as $item)
    @php $on = $path === ltrim($item['url'], '/'); @endphp
    <a class="{{ $on ? 'is-on' : '' }}" href="{{ url($item['url']) }}"><span>{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
  @endforeach
</nav>
@endsection
