@extends('layouts.app')
@section('title', 'شرح کارها | '.shop_name())
@section('page_title', 'جستجو و گزارش شرح کارها')
@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>شرح کارها</h2>
            <p class="lead">جستجو، فیلتر سطح دسترسی، چاپ گزارش</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('work-reports.print', request()->query()) }}" target="_blank">چاپ گزارش</a>
    </div>
    <form method="GET" class="ticket-search-bar" style="margin:8px 0;">
        <div class="field"><label>جستجو</label><input type="text" name="q" value="{{ $q }}" placeholder="متن شرح، قبض، سریال، مدل"></div>
        <div class="field">
            <label>سطح</label>
            <select name="visibility">
                <option value="">همه</option>
                @foreach(\App\Models\ReceptionWorkReport::VISIBILITIES as $k => $lab)
                    <option value="{{ $k }}" @selected($visibility === $k)>{{ $lab }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions" style="margin:0;"><button class="btn btn-secondary" type="submit">جستجو</button></div>
    </form>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>تاریخ</th><th>قبض</th><th>نویسنده</th><th>سطح</th><th>خلاصه</th><th></th></tr></thead>
            <tbody>
            @forelse($reports as $r)
                <tr>
                    <td>{{ jalali_like($r->created_at) }}</td>
                    <td><a href="{{ route('receptions.show', $r->reception_id) }}">{{ $r->reception?->ticket_no }}</a></td>
                    <td>{{ $r->technician?->name ?: $r->user?->name }}</td>
                    <td>{{ $r->visibilityLabel() }}</td>
                    <td>{{ $r->summary }}</td>
                    <td><a class="btn btn-ghost" href="{{ route('work-reports.similar', $r->reception_id) }}">مشابه</a></td>
                </tr>
            @empty
                <tr><td colspan="6">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $reports->links('partials.pagination') }}
</div>
@endsection
