@extends('layouts.app')
@section('title', 'سقف اعتبار نسیه | '.shop_name())
@section('page_title', 'سقف اعتبار مشتریان')
@section('window_title', 'تعریف سقف اعتبار نسیه')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">سقف اعتبار نسیه</h2>
            <p class="lead" style="margin:4px 0 0;">برای هر مشتری یک سقف بدهی مجاز تعریف کنید. بالاتر از سقف، تحویل نسیه با اخطار قرمز مسدود می‌شود. خالی = بدون سقف.</p>
        </div>
        <div class="actions" style="margin:0;">
            <a class="btn btn-ghost" href="{{ route('customers.index') }}">فهرست مشتریان</a>
            <a class="btn btn-primary" href="{{ route('customers.create') }}">مشتری جدید</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error credit-limit-blink">{{ $errors->first() }}</div>
    @endif

    <form method="GET" class="ticket-search-bar" style="margin:10px 0;">
        <div class="field" style="flex:1;">
            <label>جستجوی مشتری</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="نام یا موبایل…" autocomplete="off">
        </div>
        <div class="field">
            <label>فیلتر</label>
            <select name="filter">
                <option value="">همه</option>
                <option value="with_limit" @selected($filter === 'with_limit')>فقط با سقف تعریف‌شده</option>
            </select>
        </div>
        <div class="actions" style="margin:0;align-self:end;">
            <button class="btn btn-secondary" type="submit">جستجو</button>
            @if($q !== '' || $filter !== '')
                <a class="btn btn-ghost" href="{{ route('customers.credit-limits') }}">پاک</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>مشتری</th>
                    <th>موبایل</th>
                    <th>مانده بدهی</th>
                    <th>سقف اعتبار (تومان)</th>
                    <th>ظرفیت / وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    /** @var \App\Models\Customer $c */
                    $c = $row['customer'];
                @endphp
                <tr class="{{ $row['over'] ? 'credit-limit-row-over' : '' }}">
                    <td>
                        <a href="{{ route('customers.show', $c) }}">{{ $c->displayName() }}</a>
                        <span class="muted" style="font-size:11px;">({{ (int) ($c->receptions_count ?? 0) }} قبض)</span>
                    </td>
                    <td dir="ltr">{{ $c->phone }}</td>
                    <td style="{{ $row['open'] > 0 ? 'color:#b42318;font-weight:700;' : '' }}">{{ number_format($row['open']) }}</td>
                    <td>
                        <form method="POST" action="{{ route('customers.credit-limit', $c) }}" class="credit-limit-inline-form">
                            @csrf
                            @method('PUT')
                            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <input type="number" name="credit_limit" min="0" step="1000"
                                       value="{{ $row['limit'] !== null ? $row['limit'] : '' }}"
                                       placeholder="خالی = بدون سقف"
                                       dir="ltr" style="text-align:left;max-width:160px;">
                                <button class="btn btn-secondary" type="submit" style="padding:4px 10px;">ذخیره</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        @if($row['limit'] === null)
                            <span class="muted">بدون سقف</span>
                        @elseif($row['over'])
                            <div class="credit-limit-banner credit-limit-banner--danger credit-limit-blink" style="margin:0;padding:6px 8px;">
                                <strong>سقف پر — نسیه ممنوع</strong>
                                <span>{{ number_format($row['open'] - $row['limit']) }} بیش از سقف</span>
                            </div>
                        @else
                            <span style="color:#0f6b3a;font-weight:700;">باقی‌مانده {{ number_format($row['headroom']) }}</span>
                        @endif
                    </td>
                    <td>
                        <a class="btn btn-ghost" href="{{ route('customers.edit', $c) }}">ویرایش مشتری</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">مشتری‌ای پیدا نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">{{ $customers->links() }}</div>
</div>
@endsection
