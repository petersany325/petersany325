@extends('layouts.app')
@section('title', 'پلن و قیمت لایسنس | '.shop_name())
@section('page_title', 'پلن‌های زمانی لایسنس')
@section('window_title', 'پلن و قیمت')

@section('content')
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">پلن و قیمت لایسنس</h2>
            <p class="muted" style="margin:6px 0 0;">مثلاً ۶ ماهه یک قیمت، یک‌ساله قیمت دیگر. موقع صدور سریال از این پلن‌ها انتخاب می‌کنید.</p>
        </div>
        <a class="btn" href="{{ route('licenses.index') }}">← مرکز لایسنس</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('licenses.plans.save') }}">
        @csrf
        <div class="table-wrap">
            <table class="data" id="plans-table">
                <thead>
                <tr>
                    <th>کد</th>
                    <th>عنوان</th>
                    <th>مدت (ماه)</th>
                    <th>قیمت (تومان)</th>
                </tr>
                </thead>
                <tbody>
                @foreach($plans as $i => $plan)
                    <tr>
                        <td>
                            <input type="text" name="plans[{{ $i }}][code]" value="{{ $plan['code'] }}" required dir="ltr" style="text-align:left;">
                        </td>
                        <td>
                            <input type="text" name="plans[{{ $i }}][label]" value="{{ $plan['label'] }}" required>
                        </td>
                        <td>
                            <input type="number" name="plans[{{ $i }}][months]" value="{{ $plan['months'] }}" min="0" max="1200" placeholder="خالی = مادام‌العمر" dir="ltr" style="text-align:left;">
                            <div class="muted" style="font-size:11px;">۰ یا خالی = مادام‌العمر</div>
                        </td>
                        <td>
                            <input type="number" name="plans[{{ $i }}][price]" value="{{ (int) $plan['price'] }}" min="0" step="1000" dir="ltr" style="text-align:left;">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="muted" style="font-size:12px;">نکته: مدت از زمان نصب مشتری حساب می‌شود (مگر در صدور گزینه «از همین الان» را بزنید).</p>
        <button class="btn btn-primary" type="submit">ذخیره پلن‌ها و قیمت‌ها</button>
    </form>
</div>
@endsection
