@php
    $rs = \App\Support\ReportSettings::all();
    $periodLabels = \App\Support\ReportSettings::periodLabels();
@endphp
<div class="panel report-settings" style="margin-bottom:12px;">
    <form method="POST" action="{{ route('reports.settings') }}" class="report-settings-form">
        @csrf
        <input type="hidden" name="redirect" value="{{ url()->full() }}">
        <div class="report-settings-head">
            <div>
                <strong>تنظیمات سراسری گزارش‌ها</strong>
                <span class="muted">این بازه و گراف روی همه گزارش‌ها اعمال می‌شود.</span>
                <div class="muted" style="margin-top:4px;font-size:12px;" dir="ltr">
                    بازه فعال: {{ jalali_date($rs['from'] ?? null) }} تا {{ jalali_date($rs['to'] ?? null) }}
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
                @include('partials.jalali-date', ['name' => 'from', 'value' => $rs['from']])
            </label>
            <label>تا تاریخ
                @include('partials.jalali-date', ['name' => 'to', 'value' => $rs['to']])
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
