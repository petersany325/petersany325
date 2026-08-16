@php
    $periodLabels = \App\Support\ReportSettings::periodLabels();
    $quick = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'last_30'];
@endphp
<div class="actions no-print" style="margin:0 0 10px;flex-wrap:wrap;">
    @foreach($quick as $key)
        <form method="POST" action="{{ route('reports.settings') }}" style="display:inline;">
            @csrf
            <input type="hidden" name="redirect" value="{{ url()->full() }}">
            <input type="hidden" name="period" value="{{ $key }}">
            <input type="hidden" name="chart_type" value="{{ \App\Support\ReportSettings::chartType() }}">
            <input type="hidden" name="show_charts" value="{{ \App\Support\ReportSettings::showCharts() ? '1' : '0' }}">
            <button class="btn {{ ($period ?? '') === $key ? 'btn-primary' : 'btn-ghost' }} btn-sm" type="submit">{{ $periodLabels[$key] }}</button>
        </form>
    @endforeach
</div>
