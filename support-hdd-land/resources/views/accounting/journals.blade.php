@extends('layouts.app')
@section('title', 'اسناد حسابداری | '.shop_name())
@section('page_title', 'اسناد حسابداری')
@section('window_title', 'دفتر روزنامه')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'اسناد حسابداری',
    'accSub' => 'دفتر روزنامه دوره انتخابی',
])

<div class="acc-desk">
    <form method="GET" action="{{ route('accounting.journals') }}" class="acc-period" style="margin-bottom:10px;">
        @include('partials.jalali-date', ['name' => 'from', 'value' => $from])
        <span class="acc-period-sep">تا</span>
        @include('partials.jalali-date', ['name' => 'to', 'value' => $to])
        <input type="search" name="q" value="{{ $q }}" placeholder="شماره سند / قبض / شرح" style="min-width:160px;">
        <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
    </form>
    <section class="acc-panel">
        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead>
                <tr>
                    <th>شماره</th>
                    <th>تاریخ</th>
                    <th>شرح</th>
                    <th>مشتری</th>
                    <th>قبض</th>
                    <th>مبلغ</th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td><a class="acc-link" href="{{ route('accounting.show', $e) }}">{{ $e->entry_no }}</a></td>
                        <td>{{ jalali_date($e->entry_date) }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($e->description, 48) }}</td>
                        <td>{{ $e->customer?->name ?: '—' }}</td>
                        <td>
                            @if($e->reception)
                                <a href="{{ route('receptions.show', $e->reception) }}">{{ $e->reception->ticket_no }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="acc-num">{{ number_format($e->total_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">سندی در این بازه نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $entries->links() }}</div>
    </section>
</div>
@endsection
