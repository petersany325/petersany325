@extends('layouts.storefront')

@section('content')
@php
  $u = auth()->user();
  $path = request()->path();
  $unreadTickets = 0;
  try { $unreadTickets = \Plugins\AuthCustomers\src\Support\TicketSupport::unreadCountForUser((int) $u->id); } catch (\Throwable) {}
  $active = fn (string $prefix) => str_starts_with($path, $prefix) ? 'active' : '';
@endphp
<style>
  .cab-shell{display:grid;grid-template-columns:270px minmax(0,1fr);gap:20px;width:min(1240px,calc(100% - 28px));margin:25px auto 56px;align-items:start}
  .cab-side{position:sticky;top:16px;overflow:hidden;border:1px solid #dbe3ee;border-radius:21px;background:#f8fafc;box-shadow:0 16px 40px rgba(15,23,42,.08)}
  .cab-user{position:relative;overflow:hidden;padding:22px;color:#fff;background:linear-gradient(135deg,#172554,#1d4ed8 65%,#38bdf8)}
  .cab-user:after{content:"";position:absolute;width:105px;height:105px;left:-38px;top:-48px;border-radius:50%;background:rgba(255,255,255,.11)}
  .cab-avatar{display:grid;place-items:center;width:46px;height:46px;margin-bottom:11px;border:1px solid rgba(255,255,255,.35);border-radius:15px;background:rgba(255,255,255,.16);font-size:18px;font-weight:900}.cab-user strong,.cab-user span{position:relative;z-index:1;display:block}.cab-user strong{font-size:14px}.cab-user span{margin-top:4px;color:rgba(255,255,255,.75);font-size:10.5px;direction:ltr;text-align:right}
  .cab-nav{padding:12px}.cab-group{margin-bottom:8px;border:1px solid #e5eaf1;border-radius:13px;background:#fff}.cab-group summary{display:flex;align-items:center;justify-content:space-between;padding:11px 12px;color:#475569;cursor:pointer;list-style:none;font-size:11px;font-weight:900}.cab-group summary::-webkit-details-marker{display:none}.cab-group summary:after{content:"⌄";font-size:15px;transition:.2s}.cab-group[open] summary:after{transform:rotate(180deg)}
  .cab-links{display:grid;gap:3px;padding:0 7px 8px}.cab-links a{display:flex;align-items:center;gap:9px;min-height:38px;padding:7px 9px;border-radius:9px;color:#475569;font-size:11.5px;font-weight:750;text-decoration:none;transition:.18s}.cab-links a:hover{color:#1d4ed8;background:#eff6ff}.cab-links a.active{color:#fff;background:linear-gradient(135deg,#1d4ed8,#2563eb);box-shadow:0 7px 15px rgba(37,99,235,.2)}.cab-ico{display:grid;place-items:center;width:25px;height:25px;border-radius:8px;color:#1d4ed8;background:#dbeafe;font-style:normal;font-size:12px}.cab-links a.active .cab-ico{color:#fff;background:rgba(255,255,255,.18)}
  .cab-unread{display:inline-flex;align-items:center;justify-content:center;min-width:19px;height:19px;margin-right:auto;padding:0 5px;border-radius:999px;color:#fff;background:#ea580c;font-size:9px;font-weight:900}
  .cab-logout{padding:0 12px 13px}.cab-logout button{width:100%;height:41px;border:1px solid #fecaca;border-radius:11px;color:#b91c1c;background:#fff1f2;font-size:11.5px;font-weight:900;cursor:pointer}
  .cab-main{min-width:0}.cab-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px;padding:14px 18px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;box-shadow:0 8px 25px rgba(15,23,42,.05)}.cab-topbar strong{font-size:13px}.cab-topbar span{color:#64748b;font-size:10.5px}.cab-mobile-menu{display:none;border:0;border-radius:9px;padding:8px 11px;color:#fff;background:#1d4ed8;font-weight:800}
  .cab-alert{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:15px;padding:13px 15px;border:1px solid #fdba74;border-radius:14px;color:#9a3412;background:#fff7ed;font-size:11.5px}.cab-alert strong{display:block;color:#c2410c}.cab-alert a{white-space:nowrap}
  .cab-main .acc-card{border:1px solid #e2e8f0!important;border-radius:18px!important;background:#fff!important;box-shadow:0 10px 30px rgba(15,23,42,.055)!important}.cab-main .acc-grid{gap:13px!important}.cab-main .acc-stat{border:1px solid #e2e8f0!important;border-radius:16px!important;background:linear-gradient(145deg,#fff,#f8fafc)!important;box-shadow:0 8px 24px rgba(15,23,42,.045)!important}
  @media(max-width:850px){.cab-shell{grid-template-columns:1fr;margin-top:14px}.cab-side{position:static;display:none}.cab-shell.menu-open .cab-side{display:block}.cab-mobile-menu{display:inline-flex}.cab-topbar{position:sticky;top:7px;z-index:20}.cab-main{grid-row:2}.cab-shell.menu-open .cab-main{grid-row:auto}}
</style>
<div class="cab-shell" id="customerCabinet">
  <aside class="cab-side" aria-label="منوی کارتابل مشتری">
    <div class="cab-user">
      <div class="cab-avatar">{{ mb_substr((string)$u->name,0,1) }}</div>
      <strong>{{ $u->name }}</strong>
      <span>{{ $u->email ?: $u->phone }}</span>
    </div>
    <nav class="cab-nav">
      @foreach(\App\Support\PortalNav::customerGroups() as $group)
        <details class="cab-group" @if(($group['title'] ?? '') !== 'دسترسی سریع') open @endif>
          <summary>{{ $group['title'] }}</summary>
          <div class="cab-links">
            @foreach($group['items'] as $item)
              @php
                $match = $item['match'] ?? null;
                $isActive = $match === 'account' ? ($path === 'account') : ($match ? $active($match) : '');
              @endphp
              <a class="{{ $isActive }}" href="{{ url($item['url']) }}">
                <i class="cab-ico">{{ $item['icon'] ?? '•' }}</i>
                {{ $item['label'] }}
                @if(($item['url'] ?? '') === '/account/tickets' && $unreadTickets > 0)
                  <b class="cab-unread">{{ $unreadTickets > 9 ? '9+' : $unreadTickets }}</b>
                @endif
              </a>
            @endforeach
          </div>
        </details>
      @endforeach
    </nav>
    <form class="cab-logout" action="{{ url('/logout') }}" method="post">@csrf<button type="submit">خروج امن از حساب</button></form>
  </aside>
  <main class="cab-main">
    <div class="cab-topbar"><div><strong>کارتابل مشتری</strong><br><span>مدیریت یکپارچه حساب و خدمات</span></div><button class="cab-mobile-menu" type="button" aria-controls="customerCabinet">☰ منوی حساب</button></div>
    @if($unreadTickets > 0 && !str_starts_with($path, 'account/tickets'))
      <div class="cab-alert"><div><strong>{{ $unreadTickets }} پاسخ پشتیبانی مشاهده‌نشده دارید</strong>برای مشاهده پاسخ وارد بخش تیکت‌ها شوید.</div><a class="btn btn-primary btn-sm" href="{{ url('/account/tickets') }}">مشاهده تیکت‌ها</a></div>
    @endif
    @yield('account')
  </main>
</div>
<script>(function(){var b=document.querySelector('.cab-mobile-menu'),s=document.querySelector('#customerCabinet');if(b&&s)b.addEventListener('click',function(){s.classList.toggle('menu-open')})})();</script>
@endsection
