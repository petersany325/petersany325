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

    <div class="panel" style="padding:14px;margin-bottom:14px;">
        <h3 style="margin:0 0 10px;">کارکنان و روزهای حضور</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>کارمند</th>
                        <th>عکس مرجع</th>
                        <th>امروز</th>
                        <th>روزهای حضور (بازه)</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php $u = $row['user']; $p = $row['profile']; @endphp
                        <tr>
                            <td>
                                <strong>{{ $u->name }}</strong>
                                <div class="muted" dir="ltr">{{ $u->phone ?: '—' }}</div>
                            </td>
                            <td>
                                @if($p?->hasReferencePhoto())
                                    <a href="{{ route('attendance.reference-photo', $u) }}" target="_blank">مشاهده</a>
                                    <div class="muted" style="font-size:11px;">{{ $p->enrolled_at?->format('Y-m-d H:i') }}</div>
                                @else
                                    <span class="muted">ثبت نشده</span>
                                @endif
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
