@extends('layouts.app')
@section('title', 'همگام‌سازی رایانه | سرزمین هارد')
@section('page_title', 'همگام‌سازی نرم‌افزار رایانه')
@section('window_title', 'اتصال نرم‌افزار کامپیوتر')
@section('content')
<div class="panel">
    <h2>نرم‌افزار کامپیوتر و سایت</h2>
    <p class="lead">یک توکن برای هر رایانه بسازید و در نرم‌افزار وارد کنید. از آن به بعد مشتری، قبض، پرداخت، قطعه، تعمیرکار و منوهای پذیرش از هر دو طرف به هم می‌رسند و نسخه جدیدتر می‌ماند.</p>

    @if(!$ready)
        <div class="alert alert-error">جدول همگام‌سازی هنوز ساخته نشده. به‌روزرسانی نرم‌افزار را یک بار نصب کنید.</div>
    @endif

    @if($plainToken)
        <div class="alert alert-success">
            <div>این توکن را در نرم‌افزار رایانه وارد کنید. دوباره نشان داده نمی‌شود.</div>
            <div dir="ltr" style="margin-top:8px;font-family:monospace;word-break:break-all;">{{ $plainToken }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.desktop-sync.store') }}" class="accept-row accept-row-4">
        @csrf
        <div>
            <label>نام رایانه</label>
            <input type="text" name="name" required maxlength="80" placeholder="مثلاً پذیرش ۱" value="{{ old('name') }}">
        </div>
        <div style="align-self:end;">
            <button class="btn btn-primary" type="submit" @disabled(!$ready)>ساخت توکن</button>
        </div>
    </form>
</div>

<div class="panel" style="margin-top:12px;">
    <h3>رایانه‌های وصل‌شده</h3>
    <table class="compact-table">
        <thead>
            <tr>
                <th>نام</th>
                <th>آخرین اتصال</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
                <tr>
                    <td>{{ $device->name }}</td>
                    <td>{{ $device->last_seen_at ? jalali_like($device->last_seen_at) : 'هنوز وصل نشده' }}</td>
                    <td>{{ $device->revoked_at ? 'قطع‌شده' : 'فعال' }}</td>
                    <td>
                        @if(!$device->revoked_at)
                            <form method="POST" action="{{ route('settings.desktop-sync.revoke', $device) }}">
                                @csrf
                                <button class="btn btn-danger" type="submit" data-confirm="اتصال این رایانه قطع شود؟">قطع اتصال</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">هنوز رایانه‌ای وصل نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel" style="margin-top:12px;">
    <h3>قرارداد نرم‌افزار</h3>
    <p>درخواست‌ها هدر <span dir="ltr">Authorization: Bearer</span> و توکن را لازم دارند.</p>
    <ul>
        <li dir="ltr">GET {{ $pingUrl }}</li>
        <li dir="ltr">GET {{ $specUrl }}</li>
        <li dir="ltr">POST {{ $syncUrl }}</li>
    </ul>
    <p>بار اول <span dir="ltr">cursor</span> را خالی بفرستید. تا وقتی <span dir="ltr">has_more</span> درست است، همان <span dir="ltr">snapshot</span> را برگردانید. بعد فقط شماره <span dir="ltr">cursor</span> را نگه دارید و تغییرهای محلی را در <span dir="ltr">changes</span> بفرستید.</p>
    <pre dir="ltr" style="white-space:pre-wrap;">{
  "cursor": null,
  "changes": [
    {
      "resource": "customers",
      "sync_key": "6f1c0b2e-3a4d-4e5f-8a9b-0c1d2e3f4a5b",
      "op": "upsert",
      "base_updated_at": null,
      "data": {"name": "علی رضایی", "phone": "09120000000"}
    }
  ]
}</pre>
</div>
@endsection
