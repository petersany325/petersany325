@extends('layouts.app')
@section('title', 'لیست سیاه دستگاه | '.shop_name())
@section('page_title', 'لیست سیاه دستگاه')
@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>لیست سیاه دستگاه</h2>
            <p class="lead">سریال یا برند/مدلی که نباید دوباره پذیرش شود</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('customers.index') }}">بازگشت به مشتریان</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('device-blacklists.store') }}" class="accept-row accept-row-4" style="margin:10px 0;align-items:end;">
        @csrf
        <div>
            <label>سریال</label>
            <input type="text" name="serial_number" value="{{ old('serial_number') }}" dir="ltr" style="text-align:left;" data-ascii-en>
        </div>
        <div>
            <label>برند</label>
            <input type="text" name="brand" value="{{ old('brand') }}">
        </div>
        <div>
            <label>مدل</label>
            <input type="text" name="model" value="{{ old('model') }}" dir="ltr" style="text-align:left;">
        </div>
        <div>
            <label>دلیل</label>
            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="مثلاً سرقتی / مشکل‌دار">
        </div>
        <div>
            <button class="btn btn-primary" type="submit">افزودن</button>
        </div>
    </form>

    <form class="ticket-search-bar" method="GET" style="margin:8px 0;">
        <div class="field">
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="سریال، برند، مدل، دلیل">
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-secondary" type="submit">جستجو</button>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>سریال</th>
                    <th>برند</th>
                    <th>مدل</th>
                    <th>دلیل</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td dir="ltr">{{ $item->serial_number ?: '—' }}</td>
                    <td>{{ $item->brand ?: '—' }}</td>
                    <td dir="ltr">{{ $item->model ?: '—' }}</td>
                    <td>{{ $item->reason ?: '—' }}</td>
                    <td>
                        <span class="pill {{ $item->is_active ? 'pill-off' : 'pill-ok' }}">
                            {{ $item->is_active ? 'فعال (مسدود)' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td>
                        <div class="actions" style="margin:0;">
                            <form method="POST" action="{{ route('device-blacklists.toggle', $item) }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-secondary" type="submit">{{ $item->is_active ? 'غیرفعال' : 'فعال' }}</button>
                            </form>
                            <form method="POST" action="{{ route('device-blacklists.destroy', $item) }}" style="display:inline;" data-confirm="حذف شود؟">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">موردی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links('partials.pagination') }}
</div>
@endsection
