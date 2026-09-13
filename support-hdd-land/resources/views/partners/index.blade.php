@extends('layouts.app')

@section('title', 'همکاران شبکه | '.shop_name())
@section('page_title', 'نمایندگان / همکاران شبکه')
@section('window_title', 'جستجو با اسم مجموعه — سریال لایسنس مخفی است')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>شبکه داخلی همکاران</strong>
    <p class="muted" style="margin:6px 0 0;">فهرست از لایسنس‌های فعال با عضویت شبکه (ON) همگام می‌شود. در جستجو فقط <b>اسم مجموعه</b> دیده می‌شود؛ سریال لایسنس مشتری به همکار نشان داده نمی‌شود.</p>
    @if(!empty($sync['message']))
        <p class="muted" style="margin:8px 0 0;">آخرین همگام‌سازی: {{ $sync['message'] }}</p>
    @endif
</section>

<section class="panel">
    <form method="GET" class="accept-row accept-row-3" style="align-items:end;">
        <div>
            <label>جستجو اسم مجموعه</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="اسم مجموعه / موبایل / دامنه">
        </div>
        <div class="actions" style="margin:0;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">جستجو</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">پاک</a>
            <a class="btn btn-primary" href="{{ route('partners.cartable', ['tab' => 'pending']) }}">کارتابل ارجاع نماینده</a>
        </div>
    </form>
    <form method="POST" action="{{ route('partners.sync') }}" style="margin-top:10px;">
        @csrf
        <button class="btn btn-secondary" type="submit">همگام‌سازی شبکه</button>
    </form>
</section>

<section class="panel" style="margin-top:12px;">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>اسم مجموعه</th>
                <th>دامنه</th>
                <th>موبایل</th>
                <th>وضعیت</th>
                <th>همگام</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($partners as $p)
                <tr>
                    <td><strong>{{ $p->displayName() }}</strong></td>
                    <td dir="ltr">{{ $p->domain ?: '—' }}</td>
                    <td dir="ltr">{{ $p->phone ?: '—' }}</td>
                    <td>{{ $p->is_active ? 'فعال در شبکه' : 'غیرفعال' }}</td>
                    <td>{{ optional($p->last_synced_at)->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="actions">
                        <a class="btn btn-ghost" href="{{ route('partners.edit', $p) }}">یادداشت</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">همکار فعالی نیست. در لایسنس‌ها «اسم مجموعه» و سوییچ شبکه ON باشد؛ سپس همگام‌سازی کنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $partners->links('partials.pagination') }}
</section>
@endsection
