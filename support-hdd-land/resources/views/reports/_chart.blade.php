@php
    /** @var string $id */
    /** @var string $title */
    /** @var array $labels */
    /** @var array $values */
    /** @var string|null $type */
    $show = \App\Support\ReportSettings::showCharts();
    $type = $type ?? \App\Support\ReportSettings::chartType();
    // doughnut stays doughnut; for status mixes prefer doughnut if many categories small
    $canvasId = $id;
@endphp
@if($show)
<div class="report-chart-card">
    <h4>{{ $title }}</h4>
    <div class="report-chart-wrap">
        <canvas id="{{ $canvasId }}" height="160"
            data-chart-type="{{ $type }}"
            data-chart-labels='@json(array_values($labels))'
            data-chart-values='@json(array_values($values))'></canvas>
    </div>
</div>
@endif
