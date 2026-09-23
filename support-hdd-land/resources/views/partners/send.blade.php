@extends('layouts.app')

@section('title', 'ارجاع قبض به نماینده | '.shop_name())
@section('page_title', 'ارجاع قبض به نماینده شبکه')
@section('window_title', '۱) سرچ قبض → ۲) انتخاب نماینده → ارسال')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>منطق کامل ارجاع نماینده</strong>
    <ol class="muted" style="margin:6px 0 0;padding-right:18px;line-height:1.85;">
        <li>اول قبض را سرچ و انتخاب کنید.</li>
        <li>سپس همه نمایندگان تعریف‌شده را ببینید و با سرچ نماینده را انتخاب کنید.</li>
        <li>ارجاع به سیستم نماینده ارسال می‌شود؛ در کارتابل مقصد منتظر تأیید منشی می‌ماند.</li>
        <li>بعد تأیید: قبض اولیه مقصد + قبض ثانویه برای نماینده مبدأ؛ سپس چرخه تعمیر، SMS، هزینه، حسابداری و خروج — و در پایان ارجاع برگشت.</li>
    </ol>
    <div class="actions" style="margin-top:10px;flex-wrap:wrap;">
        <span class="btn {{ $step === 1 ? 'btn-primary' : 'btn-ghost' }}" style="pointer-events:none;">۱) انتخاب قبض</span>
        <span class="btn {{ $step === 2 ? 'btn-primary' : 'btn-ghost' }}" style="pointer-events:none;">۲) انتخاب نماینده</span>
        <a class="btn btn-ghost" href="{{ route('partners.cartable') }}">کارتابل ارجاع</a>
    </div>
</section>

@if($step === 1)
<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="{{ route('partners.send') }}" class="accept-row accept-row-3" style="align-items:end;">
        <div style="grid-column:1 / span 2;">
            <label>سرچ قبض برای ارسال *</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="شماره قبض / تیکت / سریال / نام یا موبایل مشتری" autofocus>
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-primary" type="submit">جستجوی قبض</button>
            <a class="btn btn-ghost" href="{{ route('partners.send') }}">پاک</a>
        </div>
    </form>
</section>

<section class="panel">
    <h3 style="margin-top:0;">نتایج قبض</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>مشتری</th>
                <th>دستگاه</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($receptions as $r)
                <tr>
                    <td>
                        <strong>{{ $r->receipt_no ?: $r->ticket_no }}</strong>
                        <div class="muted">{{ $r->ticket_no }}</div>
                    </td>
                    <td>
                        {{ $r->customer?->name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->customer?->phone }}</div>
                    </td>
                    <td>
                        {{ $r->product_name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->serial_number }}</div>
                    </td>
                    <td>{{ \App\Models\Reception::STATUSES[$r->status] ?? $r->status }}</td>
                    <td>
                        <a class="btn btn-primary" href="{{ route('partners.send', ['reception_id' => $r->id]) }}">انتخاب این قبض</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        @if($q !== '')
                            قبضی با این سرچ پیدا نشد.
                        @else
                            برای شروع، قبض را سرچ کنید یا از فهرست اخیر یکی را انتخاب کنید.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@else
<section class="panel" style="margin-bottom:12px;border-color:#86efac;background:#f0fdf4;">
    <strong>قبض انتخاب‌شده</strong>
    <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:10px 18px;align-items:center;">
        <div>
            <div class="muted" style="font-size:11px;">شماره قبض</div>
            <strong dir="ltr">{{ $selected->receipt_no ?: $selected->ticket_no }}</strong>
        </div>
        <div>
            <div class="muted" style="font-size:11px;">مشتری</div>
            <strong>{{ $selected->customer?->name ?: '—' }}</strong>
        </div>
        <div>
            <div class="muted" style="font-size:11px;">دستگاه / سریال</div>
            <strong>{{ $selected->product_name ?: '—' }}</strong>
            <span class="muted" dir="ltr">{{ $selected->serial_number }}</span>
        </div>
        <a class="btn btn-ghost" href="{{ route('partners.send', ['q' => $selected->receipt_no]) }}">عوض کردن قبض</a>
    </div>
</section>

<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="{{ route('partners.send') }}" class="accept-row accept-row-3" style="align-items:end;">
        <input type="hidden" name="reception_id" value="{{ $selected->id }}">
        <div style="grid-column:1 / span 2;">
            <label>سرچ نماینده / همکار شبکه</label>
            <input type="text" name="partner_q" value="{{ $partnerQ }}" placeholder="اسم مجموعه / آدرس / دامنه / موبایل" autofocus>
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-primary" type="submit">جستجوی نماینده</button>
            <a class="btn btn-ghost" href="{{ route('partners.send', ['reception_id' => $selected->id]) }}">همه نمایندگان</a>
        </div>
    </form>
</section>

<section class="panel">
    <h3 style="margin-top:0;">نمایندگان تعریف‌شده ({{ $partners->count() }})</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>اسم مجموعه</th>
                <th>آدرس</th>
                <th>دامنه</th>
                <th>موبایل</th>
                <th>ارجاع</th>
            </tr>
            </thead>
            <tbody>
            @forelse($partners as $p)
                <tr>
                    <td><strong>{{ $p->displayName() }}</strong></td>
                    <td>{{ $p->address ?: '—' }}</td>
                    <td dir="ltr">{{ $p->domain ?: '—' }}</td>
                    <td dir="ltr">{{ $p->phone ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('partners.send.refer') }}" class="accept-row" style="grid-template-columns:1fr auto;align-items:end;gap:6px;margin:0;">
                            @csrf
                            <input type="hidden" name="reception_id" value="{{ $selected->id }}">
                            <input type="hidden" name="partner_id" value="{{ $p->id }}">
                            <div>
                                <label style="font-size:10px;">یادداشت</label>
                                <input type="text" name="note" placeholder="اختیاری">
                            </div>
                            <button class="btn btn-primary" type="submit" onclick="return confirm('قبض {{ $selected->receipt_no }} به «{{ $p->displayName() }}» ارجاع شود؟');">ارجاع قبض</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">نماینده فعالی نیست. اول همگام‌سازی شبکه را از فهرست همکاران بزنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
