@extends('studio::layout')
@section('title', ($title ?? 'کارتابل مشتری').' | فراز نت آمل')
@section('content')
@php
  $tab = $tab ?? 'home';
  $menu = $menu ?? [];
  $path = '/'.ltrim(request()->path(), '/');
@endphp
<section class="ag-page sd">
  <div class="ag-wrap">
    <p class="ag-kicker">کارتابل مشتری</p>
    <h1>{{ $title ?? 'میز کار' }}</h1>
    <p class="ag-lead">تیکت، خرید، فروش، فاکتور و پشتیبانی همین‌جا به افزونه‌ها وصل است.</p>
    @if(session('success'))<div class="ag-note">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ag-note ag-note--err">{{ session('error') }}</div>@endif

    <nav class="sd-nav" aria-label="منوی کارتابل مشتری">
      @foreach($menu as $item)
        @php $on = $path === ltrim($item['url'], '/'); @endphp
        <a class="{{ $on ? 'is-on' : '' }}" href="{{ url($item['url']) }}">{{ $item['label'] }}</a>
      @endforeach
    </nav>

    @if($tab === 'home')
      <div class="ag-cards ag-cards--dense">
        <a class="ag-card" href="{{ url('/account/buy') }}"><div><b>خرید</b><p>نرم‌افزار، طراحی سایت و محصولات فروشگاه.</p></div></a>
        <a class="ag-card" href="{{ url('/account/sell') }}"><div><b>فروش / سفارش</b><p>ثبت سفارش و وصل به درگاه زرین‌پال یا ملی.</p></div></a>
        <a class="ag-card" href="{{ url('/account/tickets') }}"><div><b>تیکت</b><p>{{ !empty($ticketsReady) ? 'افزونه تیکت وصل است.' : 'تیکت داخلی فعال است.' }}</p></div></a>
        <a class="ag-card" href="{{ url('/account/support') }}"><div><b>پشتیبانی</b><p>فرم پیام و راه‌های تماس.</p></div></a>
        <a class="ag-card" href="{{ url($cartUrl ?? '/cart') }}"><div><b>سبد خرید</b><p>{{ (int)($cartCount ?? 0) }} قلم — {{ $checkoutUrl ?? '/checkout' }}</p></div></a>
        <a class="ag-card" href="{{ url('/account/invoices') }}"><div><b>فاکتورها</b><p>فاکتور هر خرید با پیامک.</p></div></a>
      </div>
    @endif

    @if(in_array($tab, ['buy','sell','prices'], true))
      <h2>نرم‌افزار و طراحی سایت</h2>
      <div class="sd-table-wrap">
        <table class="sd-table">
          <thead><tr><th>عنوان</th><th>قیمت</th><th>عمل</th></tr></thead>
          <tbody>
            @foreach(($offers ?? []) as $row)
              <tr>
                <td>{{ $row['title'] }}</td>
                <td>{{ $row['price_label'] }}</td>
                <td>
                  <a href="{{ url($row['url']) }}">معرفی</a>
                  · <a href="{{ url($row['sell_url']) }}">خرید / پرداخت</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if(($shopOffers ?? []) !== [])
        <h2 style="margin-top:1.2rem">محصولات فروشگاه</h2>
        <div class="sd-table-wrap">
          <table class="sd-table">
            <thead><tr><th>محصول</th><th>قیمت</th><th>عمل</th></tr></thead>
            <tbody>
              @foreach($shopOffers as $row)
                <tr>
                  <td>{{ $row['title'] }}</td>
                  <td>{{ $row['price_label'] }}</td>
                  <td><a href="{{ url($row['sell_url']) }}">خرید</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
      @if($tab === 'sell')
        <form class="ag-auth-form" method="post" action="{{ url('/account/sell') }}" style="margin-top:1rem">
          @csrf
          <label>مورد
            <select name="sku" required>
              @foreach(($offers ?? []) as $row)
                <option value="{{ $row['sku'] }}">{{ $row['title'] }}</option>
              @endforeach
              @foreach(($shopOffers ?? []) as $row)
                <option value="{{ $row['sku'] }}">{{ $row['title'] }}</option>
              @endforeach
            </select>
          </label>
          <label>نام<input name="buyer" value="{{ $user->name ?? '' }}" required></label>
          <label>درگاه
            <select name="gateway">
              <option value="zarinpal">زرین‌پال</option>
              <option value="melli">بانک ملی</option>
            </select>
          </label>
          <button class="ag-btn ag-btn--on" type="submit">ثبت سفارش و فاکتور</button>
        </form>
      @endif
    @endif

    @if($tab === 'orders')
      <div class="sd-table-wrap">
        <table class="sd-table">
          <thead><tr><th>شماره</th><th>عنوان</th><th>مبلغ</th><th>وضعیت</th></tr></thead>
          <tbody>
            @forelse(($orders ?? []) as $row)
              <tr>
                <td>{{ $row['number'] }}</td>
                <td>{{ $row['title'] }}</td>
                <td>{{ number_format((int)$row['total']) }}</td>
                <td>{{ $row['status'] }}</td>
              </tr>
            @empty
              <tr><td colspan="4">سفارشی نیست. از خرید یا فروش ثبت کنید.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif

    @if($tab === 'invoices')
      <div class="sd-table-wrap">
        <table class="sd-table">
          <thead><tr><th>فاکتور</th><th>عنوان</th><th>مبلغ</th><th>درگاه</th></tr></thead>
          <tbody>
            @forelse(($invoices ?? []) as $row)
              <tr>
                <td>{{ $row['id'] ?? '' }}</td>
                <td>{{ $row['title'] ?? '' }}</td>
                <td>{{ $row['price_label'] ?? '' }}</td>
                <td>{{ ($row['gateway'] ?? '') === 'melli' ? 'بانک ملی' : (($row['gateway'] ?? '') === 'zarinpal' ? 'زرین‌پال' : ($row['status'] ?? '')) }}</td>
              </tr>
            @empty
              <tr><td colspan="4">فاکتوری نیست.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif

    @if(in_array($tab, ['tickets','support'], true))
      @if($tab === 'support')
        <div class="ag-actions">
          <a class="ag-btn ag-btn--on" href="{{ url('/support') }}">صفحه پشتیبانی</a>
          <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">تماس</a>
        </div>
      @endif
      <form class="ag-auth-form" method="post" action="{{ url('/account/tickets') }}" style="margin-top:1rem">
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

    @if($tab === 'wallet')
      <p>موجودی کیف پول: <b>{{ number_format((int)($wallet['balance'] ?? 0)) }} تومان</b></p>
      <p class="ag-lead">{{ !empty($wallet['ready']) ? 'افزونه کیف پول وصل است.' : 'جدول کیف پول هنوز خالی است؛ مبلغ بعد از اتصال افزونه می‌آید.' }}</p>
    @endif

    @if($tab === 'serials')
      <ul class="ag-list">
        @forelse(($serials ?? []) as $row)
          <li><b>{{ $row['serial'] }}</b> — {{ $row['title'] }} ({{ $row['status'] }})</li>
        @empty
          <li>سریالی برای این حساب ثبت نشده. استعلام: <a href="{{ url('/serial-check') }}">گارانتی</a></li>
        @endforelse
      </ul>
    @endif
  </div>
</section>
<nav class="sd-tabbar" aria-label="منوی موبایل کارتابل مشتری">
  @foreach($menu as $item)
    @php $on = $path === ltrim($item['url'], '/'); @endphp
    <a class="{{ $on ? 'is-on' : '' }}" href="{{ url($item['url']) }}"><span>{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
  @endforeach
</nav>
@endsection
