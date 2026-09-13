@extends('layouts.app')

@section('title', 'همکاران شبکه | '.shop_name())
@section('page_title', 'نمایندگان / همکاران لایسنس‌دار')
@section('window_title', 'فقط فروشگاه‌هایی که لایسنس فعال از این نرم‌افزار دارند')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>شبکه داخلی همکاران</strong>
    <p class="muted" style="margin:6px 0 0;">این فهرست به‌صورت خودکار از لایسنس‌های فعال پر می‌شود. ارجاع قبض فقط بین همین همکاران کار می‌کند.</p>
    @if(!empty($sync['message']))
        <p class="muted" style="margin:8px 0 0;">آخرین همگام‌سازی: {{ $sync['message'] }}</p>
    @endif
</section>

<section class="panel">
    <form method="GET" class="accept-row accept-row-3" style="align-items:end;">
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="نام / دامنه / موبایل / سریال لایسنس">
        </div>
        <div class="actions" style="margin:0;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">جستجو</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">پاک</a>
            <a class="btn btn-primary" href="{{ route('partners.cartable', ['tab' => 'pending']) }}">کارتابل ارجاع نماینده</a>
        </div>
    </form>
    <form method="POST" action="{{ route('partners.sync') }}" style="margin-top:10px;">
        @csrf
        <button class="btn btn-secondary" type="submit">همگام‌سازی شبکه از لایسنس‌های فعال</button>
    </form>
</section>

<section class="panel" style="margin-top:12px;">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>نام / فروشگاه</th>
                <th>دامنه</th>
                <th>موبایل</th>
                <th>لایسنس</th>
                <th>وضعیت</th>
                <th>همگام</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($partners as $p)
                <tr>
                    <td>{{ $p->displayName() }}</td>
                    <td dir="ltr">{{ $p->domain ?: '—' }}</td>
                    <td dir="ltr">{{ $p->phone ?: '—' }}</td>
                    <td dir="ltr">{{ $p->license_key ?: '—' }}</td>
                    <td>{{ $p->is_active ? 'فعال در شبکه' : 'غیرفعال' }}</td>
                    <td>{{ optional($p->last_synced_at)->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="actions">
                        <a class="btn btn-ghost" href="{{ route('partners.edit', $p) }}">یادداشت</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">همکار فعالی نیست. لایسنس‌های فعال مشتری باید دامنه داشته باشند؛ سپس «همگام‌سازی شبکه» را بزنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $partners->links('partials.pagination') }}
</section>
@endsection
