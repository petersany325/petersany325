@extends('layouts.admin')
@section('title', 'کارت ویزیت دیجیتال')
@section('content')
@php
  $f = fn ($k, $d = '') => old($k, $s[$k] ?? $d);
  $on = fn ($k) => (bool) old($k, $s[$k] ?? false);
@endphp
<style>
.bc-admin{max-width:1180px}
.bc-head{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.bc-head h1{margin:0;font-size:1.3rem}
.bc-head p{margin:.35rem 0 0;color:#64748b;font-size:.88rem}
.bc-tabs{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.85rem}
.bc-tabs button{border:1px solid #e2e8f0;background:#fff;border-radius:999px;padding:.4rem .85rem;font:inherit;font-size:.82rem;font-weight:700;cursor:pointer}
.bc-tabs button.on{background:#e23d12;border-color:#e23d12;color:#fff}
.bc-pane{display:none}.bc-pane.on{display:block}
.bc-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem;margin-bottom:.85rem}
.bc-card h2{margin:0 0 .75rem;font-size:1rem}
.bc-grid{display:grid;gap:.65rem}
.bc-grid label{display:grid;gap:.28rem;font-size:.82rem;font-weight:700}
.bc-grid input,.bc-grid textarea,.bc-grid select{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.bc-grid .row2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
.bc-switch{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.5rem 0;border-bottom:1px dashed #eef2f7}
.bc-sw{position:relative;width:46px;height:26px;flex:0 0 auto}
.bc-sw input{opacity:0;width:0;height:0}
.bc-sw i{position:absolute;inset:0;background:#cbd5e1;border-radius:999px}
.bc-sw i:before{content:"";position:absolute;width:20px;height:20px;right:3px;top:3px;background:#fff;border-radius:50%;transition:.2s}
.bc-sw input:checked + i{background:#e23d12}
.bc-sw input:checked + i:before{transform:translateX(-20px)}
.bc-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;position:sticky;bottom:.75rem;background:rgba(248,250,252,.92);padding:.65rem;border:1px solid #e2e8f0;border-radius:12px}
.bc-hint{font-size:.78rem;color:#64748b;line-height:1.7;margin:0 0 .65rem}
.bc-table{width:100%;border-collapse:collapse;font-size:.8rem}
.bc-table th,.bc-table td{padding:.45rem .4rem;border-bottom:1px solid #eef2f7;text-align:right}
.bc-badge{display:inline-block;padding:.12rem .45rem;border-radius:999px;font-size:.7rem;font-weight:800}
.bc-badge.ok{background:#ecfdf5;color:#047857}
.bc-badge.wait{background:#fff7ed;color:#c2410c}
.bc-stats{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem}
.bc-stats span{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.4rem .7rem;font-size:.78rem;font-weight:700}
@media(max-width:700px){.bc-grid .row2{grid-template-columns:1fr}}
</style>

<div class="bc-admin">
  <div class="bc-head">
    <div>
      <h1>کارت ویزیت دیجیتال و باشگاه مشتری</h1>
      <p>طرح کارت، ذخیره در گوشی، ارسال لینک با پیامک و درخواست عضویت باشگاه مشتری سرزمین هارد.</p>
    </div>
    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
      <a class="btn" href="{{ $preview }}" target="_blank" rel="noopener">مشاهده کارت</a>
      <a class="btn" href="{{ $vcard }}" target="_blank" rel="noopener">دانلود vCard</a>
      <a class="btn" href="{{ url('/admin/homepage-settings') }}">صفحه اول</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem">{{ session('success') }}</div>
  @endif
  @if(session('sms_error'))
    <div class="alert alert-danger" style="margin-bottom:1rem">{{ session('sms_error') }}</div>
  @endif

  <div class="bc-tabs" id="bc-tabs">
    <button type="button" class="on" data-pane="id">هویت کارت</button>
    <button type="button" data-pane="links">لینک‌ها و تماس</button>
    <button type="button" data-pane="club">باشگاه مشتری</button>
    <button type="button" data-pane="sms">پنل پیامک</button>
    <button type="button" data-pane="send">ارسال لینک</button>
    <button type="button" data-pane="members">اعضا</button>
  </div>

  <form method="post" action="{{ url('/admin/biz-card') }}" id="bc-form">
    @csrf
    <div class="bc-pane on" data-pane="id">
      <div class="bc-card">
        <h2>نمایش و هویت</h2>
        <div class="bc-grid">
          <label class="bc-switch"><span>فعال بودن صفحه /card</span><span class="bc-sw"><input type="checkbox" name="enabled" value="1" @checked($on('enabled'))><i></i></span></label>
          <div class="row2">
            <label>برند<input name="brand" value="{{ $f('brand') }}"></label>
            <label>عنوان کارت<input name="title" value="{{ $f('title') }}"></label>
          </div>
          <label>خط بالای عنوان<input name="kicker" value="{{ $f('kicker') }}"></label>
          <label>معرفی کوتاه<textarea name="subtitle" rows="2">{{ $f('subtitle') }}</textarea></label>
          <div class="row2">
            <label>لوگو / آیکون<input name="logo" value="{{ $f('logo') }}" dir="ltr"></label>
            <label>عکس هیرو<input name="hero" value="{{ $f('hero') }}" dir="ltr"></label>
          </div>
          <div class="row2">
            <label>رنگ برند<input type="color" name="accent" value="{{ $f('accent', '#e23d12') }}"></label>
            <label>نام فایل vCard<input name="vcard_filename" value="{{ $f('vcard_filename') }}" dir="ltr"></label>
          </div>
          <label>یادداشت پایین کارت<input name="footer_note" value="{{ $f('footer_note') }}"></label>
          <label class="bc-switch"><span>دکمه ذخیره در گوشی</span><span class="bc-sw"><input type="checkbox" name="show_save" value="1" @checked($on('show_save'))><i></i></span></label>
          <label class="bc-switch"><span>دکمه تماس</span><span class="bc-sw"><input type="checkbox" name="show_call" value="1" @checked($on('show_call'))><i></i></span></label>
          <label class="bc-switch"><span>ردیف ارتباط مستقیم</span><span class="bc-sw"><input type="checkbox" name="show_social" value="1" @checked($on('show_social'))><i></i></span></label>
          <label class="bc-switch"><span>ردیف منوها و لینک‌های سایت</span><span class="bc-sw"><input type="checkbox" name="show_links" value="1" @checked($on('show_links'))><i></i></span></label>
        </div>
      </div>
    </div>

    <div class="bc-pane" data-pane="links">
      <div class="bc-card">
        <h2>تماس و شبکه‌ها</h2>
        <div class="bc-grid">
          <div class="row2">
            <label>تلفن دفتر<input name="phone" value="{{ $f('phone') }}" dir="ltr"></label>
            <label>ایمیل<input name="email" value="{{ $f('email') }}" dir="ltr"></label>
          </div>
          <label>آدرس دفتر<textarea name="address" rows="2">{{ $f('address') }}</textarea></label>
          <div class="row2">
            <label>لینک نقشه<input name="map_url" value="{{ $f('map_url') }}" dir="ltr"></label>
            <label>وب‌سایت<input name="website" value="{{ $f('website') }}" dir="ltr"></label>
          </div>
          <div class="row2">
            <label>برچسب واتساپ فروش<input name="wa_sales_label" value="{{ $f('wa_sales_label') }}"></label>
            <label>شماره واتساپ فروش<input name="wa_sales" value="{{ $f('wa_sales') }}" dir="ltr"></label>
          </div>
          <div class="row2">
            <label>برچسب واتساپ پشتیبانی<input name="wa_support_label" value="{{ $f('wa_support_label') }}"></label>
            <label>شماره واتساپ پشتیبانی<input name="wa_support" value="{{ $f('wa_support') }}" dir="ltr"></label>
          </div>
          <div class="row2">
            <label>تلگرام<input name="telegram" value="{{ $f('telegram') }}" dir="ltr"></label>
            <label>اینستاگرام<input name="instagram" value="{{ $f('instagram') }}" dir="ltr"></label>
          </div>
          <label>عنوان بخش ارتباط<input name="contact_section" value="{{ $f('contact_section') }}"></label>
          <label>عنوان بخش لینک‌ها<input name="links_section" value="{{ $f('links_section') }}"></label>
          <p class="bc-hint">هر خط: گروه|عنوان|توضیح|آدرس|دکمه — گروه contact یا site. میانبرها: wa:sales ، wa:support ، tg:id ، ig:id ، map:office</p>
          <label>چینش لینک‌های کارت<textarea name="links" rows="12" dir="ltr" style="text-align:right">{{ $f('links') }}</textarea></label>
        </div>
      </div>
    </div>

    <div class="bc-pane" data-pane="club">
      <div class="bc-card">
        <h2>درخواست عضویت باشگاه مشتری</h2>
        <p class="bc-hint">مشتری از روی کارت درخواست می‌دهد. سایت یک پیامک برگشت با لینک تأیید می‌فرستد. بعد از باز کردن لینک، عضویت تأیید می‌شود.</p>
        <div class="bc-grid">
          <label class="bc-switch"><span>نمایش فرم باشگاه روی کارت</span><span class="bc-sw"><input type="checkbox" name="show_club" value="1" @checked($on('show_club'))><i></i></span></label>
          <label>عنوان فرم<input name="club_title" value="{{ $f('club_title') }}"></label>
          <label>متن راهنما<textarea name="club_text" rows="3">{{ $f('club_text') }}</textarea></label>
          <label>متن دکمه<input name="club_button" value="{{ $f('club_button') }}"></label>
          <label>پیام بعد از ارسال درخواست<textarea name="club_success" rows="2">{{ $f('club_success') }}</textarea></label>
          <label>پیام بعد از تأیید لینک<textarea name="club_confirm_ok" rows="2">{{ $f('club_confirm_ok') }}</textarea></label>
        </div>
      </div>
    </div>

    <div class="bc-pane" data-pane="sms">
      <div class="bc-card">
        <h2>اتصال به پنل اس‌ام‌اس</h2>
        <p class="bc-hint">اگر پلاگین ورود مشتریان (AuthCustomers) روی سایت باشد، حالت «خودکار» همان پنل را استفاده می‌کند. در غیر این صورت کاوه‌نگار، آی‌پی‌پنل یا ملی‌پیامک را از همین صفحه تنظیم کنید. کلیدها در مخزن کد ذخیره نمی‌شوند.</p>
        <div class="bc-grid">
          <label class="bc-switch"><span>ارسال پیامک فعال باشد</span><span class="bc-sw"><input type="checkbox" name="sms_enabled" value="1" @checked($on('sms_enabled'))><i></i></span></label>
          <label>درگاه
            <select name="sms_provider">
              <option value="auto" @selected($f('sms_provider')==='auto')>خودکار — اول پنل اس‌ام‌اس سایت، بعد کلید زیر</option>
              <option value="auth" @selected($f('sms_provider')==='auth')>فقط پنل اس‌ام‌اس مشتریان سایت</option>
              <option value="kavenegar" @selected($f('sms_provider')==='kavenegar')>کاوه‌نگار</option>
              <option value="ippanel" @selected($f('sms_provider')==='ippanel')>آی‌پی‌پنل / IPPanel</option>
              <option value="melipayamak" @selected($f('sms_provider')==='melipayamak')>ملی‌پیامک</option>
            </select>
          </label>
          <div class="row2">
            <label>کلید / توکن API<input name="sms_api_key" value="{{ $f('sms_api_key') }}" dir="ltr" autocomplete="off"></label>
            <label>شماره خط ارسال<input name="sms_sender" value="{{ $f('sms_sender') }}" dir="ltr"></label>
          </div>
          <div class="row2">
            <label>نام کاربری (ملی‌پیامک)<input name="sms_username" value="{{ $f('sms_username') }}" dir="ltr" autocomplete="off"></label>
            <label>رمز (ملی‌پیامک)<input type="password" name="sms_password" value="{{ $f('sms_password') }}" dir="ltr" autocomplete="new-password"></label>
          </div>
          <label>متن پیامک ارسال لینک کارت <small>متغیرها: {link} {card} {name} {phone} {brand}</small>
            <textarea name="sms_tpl_link" rows="3">{{ $f('sms_tpl_link') }}</textarea>
          </label>
          <label>متن پیامک درخواست عضویت (لینک تأیید)
            <textarea name="sms_tpl_club" rows="3">{{ $f('sms_tpl_club') }}</textarea>
          </label>
          <label>متن پیامک بعد از تأیید ثبت‌نام
            <textarea name="sms_tpl_confirm" rows="3">{{ $f('sms_tpl_confirm') }}</textarea>
          </label>
        </div>
      </div>
    </div>

    <div class="bc-actions">
      <button class="btn btn-primary" type="submit">ذخیره تنظیمات کارت</button>
      <a class="btn" href="{{ $preview }}" target="_blank" rel="noopener">پیش‌نمایش</a>
    </div>
  </form>

  <div class="bc-pane" data-pane="send">
    <div class="bc-card">
      <h2>ارسال لینک کارت برای مشتری</h2>
      <p class="bc-hint">شماره مشتری را وارد کنید؛ سایت لینک hdd-land.ir/card را با پنل اس‌ام‌اس می‌فرستد.</p>
      <form method="post" action="{{ url('/admin/biz-card/send-link') }}" class="bc-grid">
        @csrf
        <div class="row2">
          <label>نام مشتری (اختیاری)<input name="name" placeholder="نام"></label>
          <label>موبایل<input name="phone" required placeholder="09xxxxxxxxx" dir="ltr"></label>
        </div>
        <button class="btn btn-primary" type="submit">ارسال لینک کارت با پیامک</button>
      </form>
    </div>
    <div class="bc-card">
      <h2>ثبت درخواست باشگاه از طرف ادمین</h2>
      <form method="post" action="{{ url('/admin/biz-card/send-club') }}" class="bc-grid">
        @csrf
        <div class="row2">
          <label>نام<input name="name"></label>
          <label>موبایل<input name="phone" required dir="ltr" placeholder="09xxxxxxxxx"></label>
        </div>
        <button class="btn" type="submit">ثبت و ارسال پیامک تأیید</button>
      </form>
    </div>
    <div class="bc-card">
      <h2>آخرین پیامک‌ها</h2>
      <table class="bc-table">
        <thead><tr><th>زمان</th><th>شماره</th><th>نوع</th><th>وضعیت</th><th>درگاه</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
          <tr>
            <td>{{ $log->created_at ?? '' }}</td>
            <td dir="ltr">{{ $log->phone ?? '' }}</td>
            <td>{{ $log->type ?? '' }}</td>
            <td>{{ $log->status ?? '' }}</td>
            <td>{{ $log->provider ?? '' }}</td>
          </tr>
        @empty
          <tr><td colspan="5">هنوز پیامکی از این پنل ارسال نشده.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="bc-pane" data-pane="members">
    <div class="bc-card">
      <h2>اعضای باشگاه مشتری</h2>
      <div class="bc-stats">
        <span>همه {{ $counts['all'] ?? 0 }}</span>
        <span>در انتظار {{ $counts['pending'] ?? 0 }}</span>
        <span>تأییدشده {{ $counts['confirmed'] ?? 0 }}</span>
      </div>
      <table class="bc-table">
        <thead><tr><th>نام</th><th>موبایل</th><th>وضعیت</th><th>منبع</th><th></th></tr></thead>
        <tbody>
        @forelse($members as $m)
          <tr>
            <td>{{ $m->name ?: '—' }}</td>
            <td dir="ltr">{{ $m->phone }}</td>
            <td><span class="bc-badge {{ ($m->status ?? '')==='confirmed' ? 'ok' : 'wait' }}">{{ ($m->status ?? '')==='confirmed' ? 'تأیید شده' : 'در انتظار تأیید' }}</span></td>
            <td>{{ $m->source ?? '' }}</td>
            <td>
              @if(($m->status ?? '') !== 'confirmed')
                <form method="post" action="{{ url('/admin/biz-card/members/'.$m->id.'/confirm') }}" style="display:inline">@csrf<button class="btn btn-sm" type="submit">تأیید</button></form>
              @endif
              <form method="post" action="{{ url('/admin/biz-card/members/'.$m->id.'/delete') }}" style="display:inline" onsubmit="return confirm('حذف شود؟')">@csrf<button class="btn btn-sm" type="submit">حذف</button></form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5">هنوز درخواستی ثبت نشده.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){
  var tabs = document.querySelectorAll('#bc-tabs button');
  var panes = document.querySelectorAll('.bc-pane');
  tabs.forEach(function(btn){
    btn.addEventListener('click', function(){
      tabs.forEach(function(b){ b.classList.remove('on'); });
      panes.forEach(function(p){ p.classList.remove('on'); });
      btn.classList.add('on');
      document.querySelectorAll('.bc-pane[data-pane="'+btn.getAttribute('data-pane')+'"]').forEach(function(p){ p.classList.add('on'); });
    });
  });
})();
</script>
@endsection
