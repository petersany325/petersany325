@extends('layouts.app')
@section('title', 'سطل زباله | سرزمین هارد')
@section('page_title', 'سطل زباله')
@section('window_title', 'سطل زباله — بازیابی / حذف دائم')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin:0 0 6px;">سطل زباله سیستم</h2>
    <p class="muted" style="margin:0;font-size:12px;">
        موارد حذف‌شده اینجا می‌مانند تا بازیابی یا برای همیشه پاک شوند.
        حذف دائم برگشت‌ناپذیر است (استاندارد Soft Delete / Recycle Bin).
    </p>
    <div class="accept-row accept-row-3" style="margin-top:10px;">
        <div class="pill" style="background:#fff6e5;">قبض‌ها: {{ $counts['receptions'] }}</div>
        <div class="pill" style="background:#eef6ff;">اسناد حسابداری: {{ $counts['journals'] }}</div>
        <div class="pill" style="background:#f3eefe;">مشتریان: {{ $counts['customers'] }}</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin:0 0 8px;">قبض‌های حذف‌شده</h3>
    <div class="table-wrap">
        <table class="data compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>مشتری</th>
                <th>حذف</th>
                <th>توسط</th>
                <th>دلیل</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($receptions as $rx)
                <tr>
                    <td>
                        <strong>{{ $rx->ticket_no }}</strong>
                        <div class="muted" style="font-size:11px;">{{ $rx->product_name }} · {{ $rx->serial_number ?: '—' }}</div>
                    </td>
                    <td>{{ $rx->customer?->name ?: '—' }}<div class="muted" dir="ltr">{{ $rx->customer?->phone }}</div></td>
                    <td>{{ jalali_like($rx->deleted_at) }}</td>
                    <td>{{ $rx->deleter?->name ?: '—' }}</td>
                    <td>{{ $rx->delete_reason ?: '—' }}</td>
                    <td class="actions">
                        <form method="POST" action="{{ route('trash.restore') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="type" value="reception">
                            <input type="hidden" name="id" value="{{ $rx->id }}">
                            <button class="btn btn-secondary btn-sm" type="submit">بازیابی</button>
                        </form>
                        <form method="POST" action="{{ route('trash.force') }}" style="display:inline;" data-confirm="حذف دائم این قبض و وابستگی‌ها؟ برگشت ندارد.">
                            @csrf
                            <input type="hidden" name="type" value="reception">
                            <input type="hidden" name="id" value="{{ $rx->id }}">
                            <button class="btn btn-danger btn-sm" type="submit">حذف دائم</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">قبض حذف‌شده‌ای نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin:0 0 8px;">اسناد حسابداری حذف‌شده</h3>
    <div class="table-wrap">
        <table class="data compact-table">
            <thead>
            <tr>
                <th>شماره</th>
                <th>شرح</th>
                <th>مبلغ</th>
                <th>حذف</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($journals as $je)
                <tr>
                    <td>{{ $je->entry_no }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($je->description, 48) }}
                        <div class="muted" style="font-size:11px;">{{ $je->reception?->ticket_no ?: ($je->source_type.' #'.$je->source_id) }}</div>
                    </td>
                    <td>{{ number_format((int) $je->total_amount) }}</td>
                    <td>{{ jalali_like($je->deleted_at) }}</td>
                    <td class="actions">
                        <form method="POST" action="{{ route('trash.restore') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="type" value="journal">
                            <input type="hidden" name="id" value="{{ $je->id }}">
                            <button class="btn btn-secondary btn-sm" type="submit">بازیابی</button>
                        </form>
                        <form method="POST" action="{{ route('trash.force') }}" style="display:inline;" data-confirm="حذف دائم این سند؟">
                            @csrf
                            <input type="hidden" name="type" value="journal">
                            <input type="hidden" name="id" value="{{ $je->id }}">
                            <button class="btn btn-danger btn-sm" type="submit">حذف دائم</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">سند حذف‌شده‌ای نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h3 style="margin:0 0 8px;">مشتریان حذف‌شده</h3>
    <div class="table-wrap">
        <table class="data compact-table">
            <thead>
            <tr><th>نام</th><th>موبایل</th><th>حذف</th><th>عملیات</th></tr>
            </thead>
            <tbody>
            @forelse($customers as $cu)
                <tr>
                    <td>{{ $cu->name }}</td>
                    <td dir="ltr">{{ $cu->phone }}</td>
                    <td>{{ jalali_like($cu->deleted_at) }}</td>
                    <td class="actions">
                        <form method="POST" action="{{ route('trash.restore') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="type" value="customer">
                            <input type="hidden" name="id" value="{{ $cu->id }}">
                            <button class="btn btn-secondary btn-sm" type="submit">بازیابی</button>
                        </form>
                        <form method="POST" action="{{ route('trash.force') }}" style="display:inline;" data-confirm="حذف دائم مشتری؟ اگر قبض داشته باشد ممکن است مسدود شود.">
                            @csrf
                            <input type="hidden" name="type" value="customer">
                            <input type="hidden" name="id" value="{{ $cu->id }}">
                            <button class="btn btn-danger btn-sm" type="submit">حذف دائم</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">مشتری حذف‌شده‌ای نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
