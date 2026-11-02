@extends('layouts.app')
@section('title', 'گزارش ارسال لینک کارتابل | '.shop_name())
@section('page_title', 'گزارش ارسال لینک')
@section('window_title', 'لینک کارتابل ارسال شد؟')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">گزارش ارسال لینک کارتابل</h2>
            <p class="lead" style="margin:4px 0 0;">ببینید برای چه کسانی لینک فرستاده شد و در صورت نیاز دوباره بفرستید.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('portal-invites.index') }}">ارسال گروهی</a>
    </div>

    <div class="emp-stat-row" style="margin-top:10px;">
        <div class="emp-stat"><span>کل در فیلتر</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>موفق</span><strong>{{ $stats['ok'] }}</strong></div>
        <div class="emp-stat tone-amber"><span>ناموفق</span><strong>{{ $stats['fail'] }}</strong></div>
    </div>

    <form method="GET" class="ticket-search-bar" style="margin-top:12px;">
        <div class="field">
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="نام یا موبایل">
        </div>
        <div class="field">
            <label>وضعیت</label>
            <select name="status">
                <option value="" @selected($status === '')>همه</option>
                <option value="ok" @selected($status === 'ok')>موفق</option>
                <option value="fail" @selected($status === 'fail')>ناموفق</option>
            </select>
        </div>
        <div class="field">
            <label>دسته</label>
            <select name="batch_id">
                <option value="">همه دسته‌ها</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}" @selected((int)$batchId === (int)$b->id)>{{ $b->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="field actions" style="align-self:end;">
            <button class="btn btn-primary" type="submit">اعمال</button>
        </div>
    </form>

    <div class="table-wrap" style="margin-top:10px;">
        <table class="data compact-table">
            <thead>
            <tr>
                <th>زمان</th>
                <th>مشتری</th>
                <th>موبایل</th>
                <th>نتیجه</th>
                <th>دسته</th>
                <th>فرستنده</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($sends as $send)
                <tr>
                    <td>{{ jalali_like($send->created_at) }}</td>
                    <td>{{ $send->customer?->name ?: '—' }}</td>
                    <td dir="ltr">{{ $send->phone }}</td>
                    <td>
                        @if($send->ok)
                            <span class="daybook-status ok">ارسال شد</span>
                        @else
                            <span class="daybook-status bad">ناموفق</span>
                            @if($send->provider_message)
                                <div class="muted" style="font-size:10px;">{{ \Illuminate\Support\Str::limit($send->provider_message, 60) }}</div>
                            @endif
                        @endif
                    </td>
                    <td dir="ltr">{{ $send->batch?->code ?: '—' }}</td>
                    <td>{{ $send->sender?->name ?: '—' }}</td>
                    <td>
                        @if($send->customer)
                            <form method="POST" action="{{ route('portal-invites.resend', $send->customer) }}">
                                @csrf
                                <button class="btn btn-ghost btn-sm" type="submit">ارسال مجدد</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $sends->links('partials.pagination') }}
</div>
@endsection
