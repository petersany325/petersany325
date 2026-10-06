@extends('layouts.app')
@section('title', 'مدیریت حضور و غیاب | '.shop_name())
@section('page_title', 'مدیریت حضور و غیاب')
@section('window_title', 'گزارش روزهای حضور و ثبت عکس مرجع')

@section('content')
<div class="emp-cartable">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="emp-cartable-hero">
        <div>
            <h2>مدیریت حضور و غیاب</h2>
            <p class="lead">محاسبه روزهای حضور، وضعیت امروز، و ثبت عکس اولیه فقط توسط ادمین</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-secondary" href="{{ route('attendance.index') }}">کارتابل حضور</a>
            <a class="btn btn-ghost" href="{{ route('attendance.settings') }}">تنظیمات GPS</a>
        </div>
    </div>

    <div class="panel" style="padding:14px;margin-bottom:14px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
        <div>
            <strong>دسترسی سراسری حضور و غیاب</strong>
            <div class="muted" style="font-size:13px;margin-top:4px;">
                @if($settings['enabled'])
                    سیستم برای همه فعال است. می‌توانید برای هر کارمند جداگانه غیرفعال کنید.
                @else
                    سیستم برای همه غیرفعال است — کارمندان نمی‌توانند ورود/خروج بزنند.
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('attendance.toggle-global') }}" style="display:inline;">
            @csrf
            <input type="hidden" name="enabled" value="{{ $settings['enabled'] ? '0' : '1' }}">
            <button class="btn {{ $settings['enabled'] ? 'btn-ghost' : 'btn-primary' }}" type="submit">
                {{ $settings['enabled'] ? 'غیرفعال کردن برای همه' : 'فعال کردن برای همه' }}
            </button>
        </form>
    </div>

    <form method="GET" class="actions panel" style="padding:12px;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        <label>از <input type="date" name="from" value="{{ $from }}" dir="ltr"></label>
        <label>تا <input type="date" name="to" value="{{ $to }}" dir="ltr"></label>
        <label>کارمند
            <select name="user_id">
                <option value="">همه</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected((string)$filterUserId === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </label>
        <label>وضعیت
            <select name="status">
                <option value="">همه</option>
                <option value="accepted" @selected($filterStatus==='accepted')>قبول</option>
                <option value="flagged" @selected($filterStatus==='flagged')>مشکوک</option>
                <option value="rejected" @selected($filterStatus==='rejected')>رد</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
    </form>

    @if(($pendingSelfies ?? collect())->isNotEmpty())
    <div class="panel" style="padding:14px;margin-bottom:14px;border:1px solid #f0c36d;background:#fff8e8;">
        <h3 style="margin:0 0 10px;">سلفی‌های در انتظار تأیید ({{ $pendingSelfies->count() }})</h3>
        <p class="muted" style="margin:0 0 12px;">عکس کارمند را ببینید، بعد تأیید یا رد کنید.</p>
        <div style="display:grid;gap:14px;">
            @foreach($pendingSelfies as $ps)
                @php $photoUrl = route('attendance.reference-photo', $ps->user); @endphp
                <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;padding:12px;background:#fff;border:1px solid #f0d9a0;border-radius:10px;">
                    <a href="{{ $photoUrl }}" target="_blank" title="باز کردن عکس در اندازه کامل" style="flex:0 0 auto;">
                        <img
                            src="{{ $photoUrl }}"
                            alt="سلفی {{ $ps->user?->name }}"
                            style="width:160px;height:160px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;background:#111;display:block;"
                        >
                    </a>
                    <div style="flex:1 1 220px;min-width:200px;">
                        <div style="font-size:16px;font-weight:700;margin-bottom:4px;">{{ $ps->user?->name }}</div>
                        <div class="muted" dir="ltr" style="margin-bottom:8px;">{{ $ps->user?->phone ?: '—' }}</div>
                        <div style="font-size:13px;line-height:1.7;">
                            <div>زمان ارسال: <span dir="ltr">{{ $ps->enrolled_at?->format('Y-m-d H:i') ?: '—' }}</span></div>
                            <div>
                                GPS موبایل:
                                @if($ps->phone_gps_lat !== null)
                                    <span dir="ltr">{{ number_format($ps->phone_gps_lat, 5) }}, {{ number_format($ps->phone_gps_lng, 5) }}</span>
                                    <span class="muted">(±{{ $ps->phone_gps_accuracy_m !== null ? (int) $ps->phone_gps_accuracy_m : '—' }} m)</span>
                                @else
                                    —
                                @endif
                            </div>
                            @if($ps->face_detected)
                                <div style="color:#166534;">چهره تشخیص‌شده ✓</div>
                            @endif
                        </div>
                        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <a class="btn btn-ghost" href="{{ $photoUrl }}" target="_blank">بزرگ‌نمایی عکس</a>
                            <form method="POST" action="{{ route('attendance.selfie.approve', $ps->user) }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-primary" type="submit">تأیید سلفی</button>
                            </form>
                            <form method="POST" action="{{ route('attendance.selfie.reject', $ps->user) }}" style="display:inline;" onsubmit="var r=prompt('دلیل رد (اختیاری):'); if(r!==null){ this.querySelector('[name=reason]').value=r; return true;} return false;">
                                @csrf
                                <input type="hidden" name="reason" value="">
                                <button class="btn btn-ghost" type="submit">رد</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="panel" style="padding:14px;margin-bottom:14px;">
        <h3 style="margin:0 0 10px;">کارکنان و روزهای حضور</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>کارمند</th>
                        <th>عکس مرجع</th>
                        <th>وضعیت سلفی</th>
                        <th>دسترسی</th>
                        <th>امروز</th>
                        <th>روزهای حضور (بازه)</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $u = $row['user'];
                            $p = $row['profile'];
                            $accessOn = $p === null || (bool) $p->is_active;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $u->name }}</strong>
                                <div class="muted" dir="ltr">{{ $u->phone ?: '—' }}</div>
                            </td>
                            <td>
                                @if($p?->hasReferencePhoto())
                                    <a href="{{ route('attendance.reference-photo', $u) }}" target="_blank" title="مشاهده عکس مرجع">
                                        <img
                                            src="{{ route('attendance.reference-photo', $u) }}"
                                            alt="عکس {{ $u->name }}"
                                            style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;background:#111;display:block;"
                                        >
                                    </a>
                                    <div class="muted" style="font-size:11px;margin-top:4px;">{{ $p->enrolled_at?->format('Y-m-d H:i') }}</div>
                                @else
                                    <span class="muted">ثبت نشده</span>
                                @endif
                            </td>
                            <td>{{ $p?->selfieStatusLabel() ?? 'ثبت نشده' }}</td>
                            <td>
                                <form method="POST" action="{{ route('attendance.access.toggle', $u) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="active" value="{{ $accessOn ? '0' : '1' }}">
                                    <button
                                        class="btn {{ $accessOn ? 'btn-secondary' : 'btn-ghost' }}"
                                        type="submit"
                                        title="{{ $accessOn ? 'غیرفعال کردن دسترسی این کارمند' : 'فعال کردن دسترسی این کارمند' }}"
                                    >
                                        {{ $accessOn ? 'فعال' : 'غیرفعال' }}
                                    </button>
                                </form>
                            </td>
                            <td>{{ ($row['today']['open'] ?? false) ? 'داخل شرکت' : 'خارج' }}</td>
                            <td><strong>{{ $row['present_days'] }}</strong></td>
                            <td>
                                @if(auth()->user()->isAdmin())
                                    <a class="btn btn-secondary" href="{{ route('attendance.enroll', $u) }}">ثبت / تعویض عکس مرجع</a>
                                @else
                                    <span class="muted">فقط ادمین</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel" style="padding:14px;">
        <h3 style="margin:0 0 10px;">رویدادهای بازه</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>زمان</th>
                        <th>کارمند</th>
                        <th>نوع</th>
                        <th>روش</th>
                        <th>GPS</th>
                        <th>وضعیت</th>
                        <th>عکس</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $ev)
                        <tr>
                            <td dir="ltr">{{ $ev->occurred_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $ev->user?->name }}</td>
                            <td>{{ $ev->typeLabel() }}</td>
                            <td>{{ $ev->methodLabel() }}</td>
                            <td dir="ltr" style="font-size:11px;">
                                @if($ev->latitude !== null)
                                    {{ number_format($ev->latitude, 5) }}, {{ number_format($ev->longitude, 5) }}
                                    <div>{{ $ev->distance_m !== null ? $ev->distance_m.' m' : '' }} / ±{{ $ev->accuracy_m !== null ? (int)$ev->accuracy_m : '—' }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{ $ev->statusLabel() }}
                                @if($ev->flag_reason)<div class="muted" style="font-size:11px;">{{ $ev->flag_reason }}</div>@endif
                            </td>
                            <td>
                                @if($ev->photo_path)
                                    <a href="{{ route('attendance.punch-photo', $ev) }}" target="_blank">مشاهده</a>
                                @else —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">رویدادی در این فیلتر نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
