@php
    $rs = \App\Support\ReportSettings::all();
    $periodLabels = \App\Support\ReportSettings::periodLabels();
    $periodRanges = \App\Support\ReportSettings::periodRanges();
    $periodRangesJalali = [];
    foreach ($periodRanges as $key => $pair) {
        $periodRangesJalali[$key] = [
            jalali_input($pair[0]),
            jalali_input($pair[1]),
        ];
    }
@endphp
<div class="panel report-settings" style="margin-bottom:12px;"
     data-report-settings
     data-period-ranges='@json($periodRangesJalali)'>
    <form method="POST" action="{{ route('reports.settings') }}" class="report-settings-form">
        @csrf
        <input type="hidden" name="redirect" value="{{ url()->current() }}">
        <div class="report-settings-head">
            <div>
                <strong>تنظیمات سراسری گزارش‌ها</strong>
                <span class="muted">این بازه و گراف روی همه گزارش‌ها اعمال می‌شود.</span>
                <div class="muted" style="margin-top:4px;font-size:12px;" dir="ltr">
                    بازه فعال: {{ jalali_date($rs['from'] ?? null) }} تا {{ jalali_date($rs['to'] ?? null) }}
                    @if(!empty($rs['period']) && ($periodLabels[$rs['period']] ?? null))
                        — {{ $periodLabels[$rs['period']] }}
                    @endif
                </div>
            </div>
            <button class="btn btn-primary" type="submit">اعمال</button>
        </div>
        <div class="report-settings-grid">
            <label>بازه سریع
                <select name="period" id="report-period">
                    @foreach($periodLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($rs['period'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>از تاریخ
                @include('partials.jalali-date', ['name' => 'from', 'id' => 'report-from', 'value' => $rs['from']])
            </label>
            <label>تا تاریخ
                @include('partials.jalali-date', ['name' => 'to', 'id' => 'report-to', 'value' => $rs['to']])
            </label>
            <label>نوع گراف
                <select name="chart_type">
                    <option value="bar" @selected(($rs['chart_type'] ?? '') === 'bar')>میله‌ای</option>
                    <option value="line" @selected(($rs['chart_type'] ?? '') === 'line')>خطی</option>
                    <option value="doughnut" @selected(($rs['chart_type'] ?? '') === 'doughnut')>حلقه‌ای</option>
                </select>
            </label>
            <div>
                @include('partials.toggle', [
                    'name' => 'show_charts',
                    'label' => 'نمایش گراف در گزارش‌ها',
                    'checked' => !empty($rs['show_charts']),
                ])
            </div>
        </div>
    </form>
</div>
@once
@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-report-settings]');
    if (!root) return;
    var ranges = {};
    try { ranges = JSON.parse(root.getAttribute('data-period-ranges') || '{}'); } catch (e) { ranges = {}; }
    var period = document.getElementById('report-period');
    var from = document.getElementById('report-from');
    var to = document.getElementById('report-to');
    if (!period || !from || !to) return;

    function syncDates() {
        var key = period.value;
        if (!key || key === 'custom') return;
        var pair = ranges[key];
        if (!pair || !pair.length) return;
        from.value = pair[0] || '';
        to.value = pair[1] || '';
    }

    period.addEventListener('change', function () {
        if (period.value === 'custom') return;
        syncDates();
    });

    from.addEventListener('input', function () { period.value = 'custom'; });
    to.addEventListener('input', function () { period.value = 'custom'; });
})();
</script>
@endpush
@endonce
