@extends('layouts.app')
@section('title', 'جزئیات اقساط #'.$plan->id.' | '.shop_name())
@section('page_title', 'طرح اقساط #'.$plan->id)
@section('window_title', ($plan->customer?->displayName() ?? 'اقساط').' — '.$plan->statusLabel())

@section('content')
@include('accounting._nav', [
    'accTitle' => 'جزئیات اقساط',
    'accSub' => ($plan->title ?: 'بدون عنوان').' — '.$plan->statusLabel(),
])

@php
    $paid = (int) $plan->items->sum('paid_amount');
    $total = (int) $plan->items->sum('amount');
    $remain = max(0, $total - $paid);
@endphp

<div class="acc-desk">
    <div class="acc-kpi-grid">
        <div class="acc-kpi tone-teal">
            <span class="acc-kpi-label">مبلغ کل</span>
            <strong class="acc-kpi-value">{{ number_format($total) }}</strong>
        </div>
        <div class="acc-kpi tone-amber">
            <span class="acc-kpi-label">پرداخت‌شده</span>
            <strong class="acc-kpi-value">{{ number_format($paid) }}</strong>
        </div>
        <div class="acc-kpi tone-rose">
            <span class="acc-kpi-label">مانده</span>
            <strong class="acc-kpi-value">{{ number_format($remain) }}</strong>
        </div>
    </div>

    <div class="acc-panels" style="grid-template-columns:1.2fr .8fr;">
        <section class="acc-panel">
            <header class="acc-panel-head">
                <h3>اقساط</h3>
                <div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('installments.index') }}">لیست</a>
                    <a class="btn btn-ghost btn-sm" href="{{ route('installments.report', ['customer_id' => $plan->customer_id]) }}">گزارش مشتری</a>
                </div>
            </header>
            <p style="margin:0 0 10px;">
                مشتری:
                @if($plan->customer)
                    <a class="acc-link" href="{{ route('customers.show', $plan->customer) }}">{{ $plan->customer->displayName() }}</a>
                    <span class="muted" dir="ltr">({{ $plan->customer->phone }})</span>
                @endif
                @if($plan->reception)
                    — قبض <a class="acc-link" href="{{ route('receptions.show', $plan->reception) }}">{{ $plan->reception->ticket_no }}</a>
                @endif
            </p>

            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>سررسید</th>
                        <th>مبلغ</th>
                        <th>پرداختی</th>
                        <th>مانده</th>
                        <th>وضعیت</th>
                        <th>یادداشت</th>
                        <th>دریافت</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($plan->items as $item)
                        <tr>
                            <td>{{ $item->sequence }}</td>
                            <td dir="ltr">{{ jalali_date($item->due_date) }}</td>
                            <td class="acc-num">{{ number_format($item->amount) }}</td>
                            <td class="acc-num">{{ number_format($item->paid_amount) }}</td>
                            <td class="acc-num">{{ number_format($item->remainingAmount()) }}</td>
                            <td>{{ $item->statusLabel() }}</td>
                            <td style="min-width:140px;">
                                <form method="post" action="{{ route('installments.items.notes', $item) }}" style="display:flex;gap:4px;">
                                    @csrf
                                    <input type="text" name="notes" value="{{ $item->notes }}" maxlength="2000" style="flex:1;min-width:80px;">
                                    <button class="btn btn-ghost btn-sm" type="submit">ذخیره</button>
                                </form>
                            </td>
                            <td style="min-width:220px;">
                                @if($plan->status === 'active' && $item->remainingAmount() > 0 && $item->status !== 'cancelled')
                                    <form method="post" action="{{ route('installments.items.collect', $item) }}" class="form-grid" style="gap:4px;grid-template-columns:1fr 1fr;">
                                        @csrf
                                        <input type="number" name="amount" min="1" max="{{ $item->remainingAmount() }}" value="{{ $item->remainingAmount() }}" required dir="ltr" title="مبلغ">
                                        <select name="method" required>
                                            @foreach($methods as $k => $lab)
                                                <option value="{{ $k }}">{{ $lab }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="note" placeholder="یادداشت دریافت" style="grid-column:1/-1;">
                                        <details style="grid-column:1/-1;">
                                            <summary class="muted" style="cursor:pointer;font-size:11px;">جزئیات چک (اگر روش=چک)</summary>
                                            <div class="form-grid" style="margin-top:6px;">
                                                <input type="text" name="check[check_number]" placeholder="شماره چک">
                                                <input type="text" name="check[bank_name]" placeholder="بانک">
                                                <input type="text" name="check[branch]" placeholder="شعبه">
                                                <input type="text" name="check[holder_name]" placeholder="صاحب چک">
                                                @include('partials.jalali-date', ['name' => 'check[due_date]', 'value' => ''])
                                                <label style="font-size:11px;"><input type="checkbox" name="check[is_custody]" value="1" checked> امانی</label>
                                            </div>
                                        </details>
                                        <button class="btn btn-primary btn-sm" type="submit" style="grid-column:1/-1;">ثبت پرداخت (ناقص هم مجاز)</button>
                                    </form>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="acc-panel">
            <header class="acc-panel-head"><h3>اطلاعات / ضامن</h3></header>
            <form method="post" action="{{ route('installments.update', $plan) }}" class="form-grid">
                @csrf
                @method('PUT')
                <label>عنوان<input type="text" name="title" value="{{ old('title', $plan->title) }}"></label>
                <label style="grid-column:1/-1;">یادداشت طرح<textarea name="notes" rows="3">{{ old('notes', $plan->notes) }}</textarea></label>
                <label>نام ضامن<input type="text" name="guarantor_name" value="{{ old('guarantor_name', $plan->guarantor_name) }}"></label>
                <label>موبایل ضامن<input type="text" name="guarantor_phone" value="{{ old('guarantor_phone', $plan->guarantor_phone) }}" dir="ltr"></label>
                <label>کد ملی<input type="text" name="guarantor_national_code" value="{{ old('guarantor_national_code', $plan->guarantor_national_code) }}" dir="ltr"></label>
                <label>آدرس<input type="text" name="guarantor_address" value="{{ old('guarantor_address', $plan->guarantor_address) }}"></label>
                <label style="grid-column:1/-1;">توضیح ضامن<textarea name="guarantor_notes" rows="2">{{ old('guarantor_notes', $plan->guarantor_notes) }}</textarea></label>
                <button class="btn btn-primary btn-sm" type="submit">ذخیره</button>
            </form>

            @if($plan->status === 'active')
                <form method="post" action="{{ route('installments.cancel', $plan) }}" style="margin-top:16px;" onsubmit="return confirm('لغو طرح؟ اقساط پرداخت‌نشده لغو می‌شوند.');">
                    @csrf
                    <input type="text" name="reason" placeholder="دلیل لغو" style="width:100%;margin-bottom:6px;">
                    <button class="btn btn-ghost btn-sm" type="submit" style="color:#b42318;">لغو طرح</button>
                </form>
            @endif
        </section>
    </div>

    <div class="acc-panels" style="grid-template-columns:1fr 1fr;margin-top:12px;">
        <section class="acc-panel">
            <header class="acc-panel-head"><h3>چک‌های امانی</h3></header>
            <form method="post" action="{{ route('installments.checks.store', $plan) }}" class="form-grid" style="margin-bottom:12px;">
                @csrf
                <label>قسط مرتبط
                    <select name="installment_item_id">
                        <option value="">—</option>
                        @foreach($plan->items as $it)
                            <option value="{{ $it->id }}">قسط {{ $it->sequence }}</option>
                        @endforeach
                    </select>
                </label>
                <label>مبلغ *<input type="number" name="amount" min="1" required dir="ltr"></label>
                <label>شماره چک<input type="text" name="check_number"></label>
                <label>بانک<input type="text" name="bank_name"></label>
                <label>شعبه<input type="text" name="branch"></label>
                <label>صاحب چک<input type="text" name="holder_name"></label>
                <label>سررسید چک
                    @include('partials.jalali-date', ['name' => 'due_date', 'value' => ''])
                </label>
                <label><input type="checkbox" name="is_custody" value="1" checked> امانی نزد ما</label>
                <label style="grid-column:1/-1;">یادداشت<textarea name="notes" rows="2"></textarea></label>
                <button class="btn btn-primary btn-sm" type="submit">ثبت چک</button>
            </form>

            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead><tr><th>شماره</th><th>مبلغ</th><th>سررسید</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                    @forelse($plan->checks as $check)
                        <tr>
                            <td>{{ $check->check_number ?: '—' }}<div class="muted" style="font-size:11px;">{{ $check->bank_name }} {{ $check->holder_name }}</div></td>
                            <td class="acc-num">{{ number_format($check->amount) }}</td>
                            <td dir="ltr">{{ $check->due_date ? jalali_date($check->due_date) : '—' }}</td>
                            <td>{{ $check->statusLabel() }}@if($check->is_custody) <span class="muted">(امانی)</span>@endif</td>
                            <td>
                                <form method="post" action="{{ route('installments.checks.update', $check) }}">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" onchange="this.form.submit()">
                                        @foreach($checkStatuses as $k => $lab)
                                            <option value="{{ $k }}" @selected($check->status === $k)>{{ $lab }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">چکی ثبت نشده.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="acc-panel">
            <header class="acc-panel-head"><h3>تاریخچه پرداخت</h3></header>
            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead><tr><th>تاریخ</th><th>قسط</th><th>مبلغ</th><th>روش</th><th>یادداشت</th></tr></thead>
                    <tbody>
                    @forelse($plan->payments->sortByDesc('paid_at') as $p)
                        <tr>
                            <td dir="ltr">{{ jalali_like($p->paid_at) }}</td>
                            <td>{{ $p->item?->sequence ?? '—' }}</td>
                            <td class="acc-num">{{ number_format($p->amount) }}</td>
                            <td>{{ $p->methodLabel() }}</td>
                            <td>{{ $p->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">پرداختی ثبت نشده.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
