@extends('layouts.app')
@section('title', 'هزینه‌های ثابت | '.shop_name())
@section('page_title', 'هزینه‌های ثابت')
@section('window_title', 'اجاره، حقوق، آب و برق و …')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">هزینه‌های ثابت</h2>
    <p class="lead" style="margin:0 0 10px;">تعریف هزینه ماهانه و ثبت سند حسابداری (بدهکار ۵۳۱۰ / بستانکار صندوق یا بانک).</p>
    <div class="stats stats-compact">
        <div class="stat"><div class="label">جمع ماهانه فعال</div><div class="value">{{ number_format($activeSum) }}</div></div>
        <div class="stat"><div class="label">معوق ماه {{ $month }}</div><div class="value">{{ $dueCount }}</div></div>
    </div>
    <form method="GET" action="{{ route('accounting.fixed-costs.index') }}" class="actions" style="margin-top:10px;gap:8px;align-items:end;">
        <div>
            <label>ماه سند</label>
            <input type="month" name="month" value="{{ $month }}" dir="ltr">
        </div>
        <button class="btn btn-ghost" type="submit">نمایش</button>
    </form>
    <form method="POST" action="{{ route('accounting.fixed-costs.post') }}" data-confirm="هزینه‌های معوق این ماه سند شوند؟" style="margin-top:8px;">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-primary" type="submit" @disabled($dueCount < 1)>ثبت سند ماه {{ $month }} ({{ $dueCount }} مورد)</button>
    </form>
</div>

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">فهرست هزینه‌ها</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>مبلغ</th>
                    <th>پرداخت از</th>
                    <th>آخرین سند</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($costs as $cost)
                    <tr>
                        <td>{{ $cost->title }}</td>
                        <td>{{ $cost->category ?: '—' }}</td>
                        <td>{{ number_format((int) $cost->amount) }}</td>
                        <td>
                            {{ $cost->payMethodLabel() }}
                            @if($cost->bankAccount)<div class="muted" style="font-size:11px;">{{ $cost->bankAccount->name }}</div>@endif
                        </td>
                        <td dir="ltr">{{ $cost->last_posted_period ?: '—' }}</td>
                        <td>
                            {{ $cost->is_active ? 'فعال' : 'غیرفعال' }}
                            @if($cost->isDueInPeriod($month))
                                <span class="badge">معوق</span>
                            @endif
                        </td>
                        <td>
                            <details>
                                <summary class="btn btn-ghost">ویرایش</summary>
                                <form method="POST" action="{{ route('accounting.fixed-costs.update', $cost) }}" style="margin-top:8px;">
                                    @csrf @method('PUT')
                                    @include('accounting._fixed-cost-form-fields', ['cost' => $cost, 'banks' => $banks])
                                    <div class="actions">
                                        <button class="btn btn-secondary" type="submit">ذخیره</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('accounting.fixed-costs.post') }}" style="margin-top:6px;">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="cost_id" value="{{ $cost->id }}">
                                    <button class="btn btn-ghost" type="submit" @disabled(! $cost->isDueInPeriod($month))>سند همین مورد</button>
                                </form>
                                <form method="POST" action="{{ route('accounting.fixed-costs.destroy', $cost) }}" data-confirm="حذف شود؟" style="margin-top:6px;">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger" type="submit">حذف</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">هنوز هزینه ثابتی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">هزینه جدید</h3>
        <form method="POST" action="{{ route('accounting.fixed-costs.store') }}">
            @csrf
            @include('accounting._fixed-cost-form-fields', ['cost' => null, 'banks' => $banks])
            <div class="actions">
                <button class="btn btn-primary" type="submit">ثبت هزینه</button>
            </div>
        </form>
    </div>
</div>
@endsection
