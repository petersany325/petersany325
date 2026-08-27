@extends('layouts.app')
@section('title', 'دفتر معین | '.shop_name())
@section('page_title', 'دفتر معین')
@section('window_title', 'دفتر معین '.$account->code)

@section('content')
@include('accounting._nav', [
    'accTitle' => 'دفتر معین '.$account->code,
    'accSub' => $account->name,
])

<div class="acc-desk">
    <form method="GET" action="{{ route('accounting.ledger') }}" class="acc-period" style="margin-bottom:10px;">
        <select name="account">
            @foreach($accounts as $a)
                <option value="{{ $a->code }}" @selected($a->id === $account->id)>{{ $a->code }} — {{ $a->name }}</option>
            @endforeach
        </select>
        @include('partials.jalali-date', ['name' => 'from', 'value' => $from])
        <span class="acc-period-sep">تا</span>
        @include('partials.jalali-date', ['name' => 'to', 'value' => $to])
        <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
    </form>
    <section class="acc-panel">
        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>سند</th>
                    <th>شرح</th>
                    <th>بدهکار</th>
                    <th>بستانکار</th>
                    <th>مانده</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php $line = $row['line']; @endphp
                    <tr>
                        <td>{{ jalali_date($line->entry?->entry_date) }}</td>
                        <td>
                            @if($line->entry)
                                <a class="acc-link" href="{{ route('accounting.show', $line->entry) }}">{{ $line->entry->entry_no }}</a>
                            @endif
                        </td>
                        <td>{{ $line->memo ?: ($line->entry?->description ?: '—') }}</td>
                        <td class="acc-num">{{ $line->debit ? number_format($line->debit) : '—' }}</td>
                        <td class="acc-num">{{ $line->credit ? number_format($line->credit) : '—' }}</td>
                        <td class="acc-num">{{ number_format($row['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">ردیفی در این بازه نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:8px;">مانده پایانی دوره: <strong>{{ number_format($running) }}</strong> تومان</p>
    </section>
</div>
@endsection
