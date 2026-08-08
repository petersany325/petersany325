@extends('layouts.app')
@section('title', 'مرکز لایسنس | '.shop_name())
@section('page_title', 'مرکز لایسنس مشتریان')
@section('window_title', 'ساخت و گزارش لایسنس')

@section('content')
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">مرکز لایسنس</h2>
            <p class="muted" style="margin:6px 0 0;">صدور سریال، ارسال به مشتری، و پایش آنلاین بودن نصب‌ها</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn btn-primary" href="#issue-box">+ ساخت لایسنس</a>
            <a class="btn" href="{{ route('licenses.plans') }}">پلن و قیمت</a>
            <a class="btn" href="{{ route('licenses.online') }}">گزارش آنلاین</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="accept-row accept-row-4" style="margin-bottom:16px;">
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">کل سریال‌ها</div>
            <div style="font-size:22px;font-weight:800;">{{ $stats['total'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">فعال</div>
            <div style="font-size:22px;font-weight:800;color:#0f6b3a;">{{ $stats['active'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">آنلاین (۷ روز)</div>
            <div style="font-size:22px;font-weight:800;color:#1d4f91;">{{ $stats['online'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">آفلاین / بی‌پاسخ</div>
            <div style="font-size:22px;font-weight:800;color:#9a3412;">{{ $stats['offline'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">استفاده‌نشده</div>
            <div style="font-size:22px;font-weight:800;">{{ $stats['unused'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">باطل / منقضی</div>
            <div style="font-size:22px;font-weight:800;">{{ $stats['revoked'] + $stats['expired'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">صدور ۳۰ روز</div>
            <div style="font-size:22px;font-weight:800;">{{ $stats['issued_30d'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">فعال‌سازی ۳۰ روز</div>
            <div style="font-size:22px;font-weight:800;">{{ $stats['activated_30d'] }}</div>
        </div>
    </div>

    <div id="issue-box" class="panel" style="background:#f7fafc;margin-bottom:16px;">
        <h3 style="margin-top:0;">ساخت لایسنس جدید</h3>
        <form method="POST" action="{{ route('licenses.issue') }}">
            @csrf
            <div class="accept-row accept-row-3" style="align-items:end;">
                <div>
                    <label>نام مشتری / تعمیرگاه</label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="مثلاً تعمیرگاه آریا">
                </div>
                <div>
                    <label>موبایل</label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" dir="ltr" style="text-align:left;" placeholder="09xxxxxxxxx">
                </div>
                <div>
                    <label>ایمیل</label>
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" dir="ltr" style="text-align:left;">
                </div>
                <div>
                    <label>پلن زمانی / قیمت</label>
                    <select name="plan_code" required>
                        @foreach($plans as $plan)
                            <option value="{{ $plan['code'] }}" @selected(old('plan_code', 'y1') === $plan['code'])>
                                {{ $plan['label'] }}
                                — {{ $plan['price'] > 0 ? number_format($plan['price']).' تومان' : 'قیمت تنظیم نشده' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="muted" style="font-size:11px;margin-top:4px;">
                        <a href="{{ route('licenses.plans') }}">ویرایش پلن و قیمت‌ها</a>
                    </div>
                </div>
                <div>
                    <label>شروع اعتبار</label>
                    <select name="start_from">
                        <option value="activate" @selected(old('start_from', 'activate') === 'activate')>از زمان نصب / فعال‌سازی</option>
                        <option value="issue" @selected(old('start_from') === 'issue')>از همین الان (صدور)</option>
                    </select>
                </div>
                <div>
                    <label>دامنه پیشنهادی (اختیاری)</label>
                    <input type="text" name="domain_hint" value="{{ old('domain_hint') }}" dir="ltr" style="text-align:left;" placeholder="shop.example.com">
                </div>
                <div>
                    <label>انقضای دستی (اختیاری — جایگزین پلن)</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at') }}">
                </div>
                <div>
                    <label>یادداشت</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="مثلاً فاکتور ۱۲۳">
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-top:10px;">
                @include('partials.toggle', [
                    'name' => 'send_sms',
                    'label' => 'بعد از ساخت، سریال را SMS کن',
                    'checked' => (bool) old('send_sms', false),
                    'on' => 'ارسال',
                    'off' => 'بدون SMS',
                ])
                <button class="btn btn-primary" type="submit">صدور سریال</button>
            </div>
        </form>
    </div>

    <form method="GET" action="{{ route('licenses.index') }}" class="accept-row accept-row-4" style="align-items:end;margin-bottom:12px;">
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $search }}" placeholder="سریال / نام / دامنه / موبایل">
        </div>
        <div>
            <label>وضعیت</label>
            <select name="status">
                <option value="">همه</option>
                <option value="unused" @selected($status==='unused')>استفاده‌نشده</option>
                <option value="active" @selected($status==='active')>فعال</option>
                <option value="revoked" @selected($status==='revoked')>باطل</option>
                <option value="expired" @selected($status==='expired')>منقضی</option>
            </select>
        </div>
        <div>
            <label>آنلاین بودن</label>
            <select name="online">
                <option value="">—</option>
                <option value="1" @selected($online==='1')>آنلاین (۷ روز)</option>
                <option value="0" @selected($online==='0')>آفلاین / بی‌پاسخ</option>
            </select>
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn" type="submit">فیلتر</button>
            <a class="btn" href="{{ route('licenses.index') }}">پاک</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>سریال</th>
                <th>مشتری</th>
                <th>پلن / قیمت</th>
                <th>دامنه</th>
                <th>وضعیت</th>
                <th>شروع (شمسی)</th>
                <th>پایان (شمسی)</th>
                <th>آنلاین</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($licenses as $row)
                <tr>
                    <td>
                        <code dir="ltr" style="font-weight:800;letter-spacing:.04em;">{{ $row->license_key }}</code>
                        @if($row->notes)
                            <div class="muted" style="font-size:11px;">{{ $row->notes }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $row->customer_name ?: '—' }}
                        <div class="muted" dir="ltr" style="font-size:11px;">{{ $row->customer_phone }}</div>
                        <div class="muted" dir="ltr" style="font-size:11px;">{{ $row->customer_email }}</div>
                    </td>
                    <td>
                        <div>{{ $row->plan_label ?: '—' }}</div>
                        <div class="muted" style="font-size:11px;">
                            @if($row->price_toman)
                                {{ number_format((int) $row->price_toman) }} تومان
                            @else
                                —
                            @endif
                        </div>
                    </td>
                    <td dir="ltr">{{ $row->domain ?: ($row->meta['domain_hint'] ?? '—') }}</td>
                    <td>{{ $row->statusLabel() }}</td>
                    <td>
                        @if($row->startsAt())
                            {{ jalali_date($row->startsAt()) }}
                            @if(! $row->activated_at)
                                <div class="muted" style="font-size:11px;">صدور</div>
                            @else
                                <div class="muted" style="font-size:11px;">فعال‌سازی</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($row->expires_at)
                            {{ jalali_date($row->expires_at) }}
                        @elseif($row->plan_months && ! $row->activated_at)
                            <span class="muted">{{ $row->plan_months }} ماه از نصب</span>
                        @else
                            مادام‌العمر
                        @endif
                    </td>
                    <td>
                        @if($row->status === 'active')
                            <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $row->isOnline() ? '#e8f8ef' : '#fff7ed' }};color:{{ $row->isOnline() ? '#0f6b3a' : '#9a3412' }};">{{ $row->onlineLabel() }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="min-width:220px;">
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            <a class="btn" href="{{ route('licenses.edit', $row) }}" style="padding:4px 8px;font-size:11px;text-decoration:none;">ویرایش</a>
                            @if($row->status !== 'revoked')
                                <a class="btn btn-primary" href="{{ route('licenses.renew', $row) }}" style="padding:4px 8px;font-size:11px;text-decoration:none;">تمدید</a>
                            @endif
                            @if($row->customer_phone)
                                <form method="POST" action="{{ route('licenses.sms', $row) }}">
                                    @csrf
                                    <button class="btn" type="submit" style="padding:4px 8px;font-size:11px;">SMS</button>
                                </form>
                            @endif
                            @if($row->status === 'active')
                                <form method="POST" action="{{ route('licenses.unbind', $row) }}" onsubmit="return confirm('قفل دامنه برداشته شود؟');">
                                    @csrf
                                    <button class="btn" type="submit" style="padding:4px 8px;font-size:11px;">آزادسازی</button>
                                </form>
                            @endif
                            @if(in_array($row->status, ['unused','active','expired'], true))
                                <form method="POST" action="{{ route('licenses.revoke', $row) }}" onsubmit="return confirm('این لایسنس باطل شود؟');">
                                    @csrf
                                    <button class="btn" type="submit" style="padding:4px 8px;font-size:11px;background:#b42318;color:#fff;border:0;">باطل</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">سریالی نیست. از فرم بالا بسازید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $licenses->links('partials.pagination') }}
</div>
@endsection
