@extends('layouts.staff')
@section('title','گزارش کار')
@section('content')
@php $nf = fn ($n) => number_format((int) $n); @endphp
<div class="row" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.8rem">
  <div>
    <h1 style="margin:0">گزارش کار و سود</h1>
      <p class="muted" style="margin:.3rem 0 0">نرخ کمیسیون شما: {{ $rate }}٪ · گزارش کار پایین صفحه</p>
  </div>
  <form method="get" class="row" style="gap:.4rem">
    <input type="number" name="days" value="{{ $days }}" min="1" max="90" style="width:90px">
    <button class="btn btn-primary btn-sm" type="submit">اعمال</button>
  </form>
</div>

<div class="staff-grid">
  <div class="staff-stat"><span class="muted">فروش</span><strong>{{ $nf($summary['gross']) }}</strong></div>
  <div class="staff-stat"><span class="muted">کمیسیون</span><strong>{{ $nf($summary['commission']) }}</strong></div>
  <div class="staff-stat"><span class="muted">سفارش</span><strong>{{ $summary['orders'] }}</strong></div>
  @if($canProfit)
    <div class="staff-stat"><span class="muted">هزینه</span><strong>{{ $nf($summary['cost']) }}</strong></div>
    <div class="staff-stat"><span class="muted">سود</span><strong style="color:#0f7a4b">{{ $nf($summary['profit']) }}</strong></div>
    <div class="staff-stat"><span class="muted">حاشیه</span><strong>{{ $summary['margin'] }}٪</strong></div>
  @endif
</div>

<div class="panel" style="margin-top:1rem">
  <table class="table">
    <thead><tr><th>تاریخ</th><th>سفارش</th><th>فروش</th><th>کمیسیون</th>@if($canProfit)<th>سود</th><th>٪</th>@endif</tr></thead>
    <tbody>
    @foreach($byDay as $row)
      <tr>
        <td>{{ $row['date'] }}</td>
        <td>{{ $row['orders'] }}</td>
        <td>{{ $nf($row['gross']) }}</td>
        <td>{{ $nf($row['commission']) }}</td>
        @if($canProfit)<td>{{ $nf($row['profit']) }}</td><td>{{ $row['margin'] }}٪</td>@endif
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="panel" style="margin-top:1rem">
  <h3 style="margin-top:0">نمودار رشد ماهانه</h3>
  <canvas id="myStaffChart" height="120"></canvas>
</div>

<div class="panel" style="margin-top:1rem">
  <h3 style="margin-top:0">گزارش کار من (ورود / خروج / فعالیت)</h3>
  <div class="staff-grid" style="margin-bottom:.8rem">
    @foreach(($activityCounts ?? []) as $a => $c)
      <div class="staff-stat"><span class="muted">{{ $a }}</span><strong>{{ $c }}</strong></div>
    @endforeach
  </div>
  <table class="table">
    <thead><tr><th>زمان</th><th>رویداد</th><th>مسیر</th></tr></thead>
    <tbody>
    @forelse(($activity ?? collect()) as $log)
      <tr>
        <td>{{ substr((string)$log->created_at,0,16) }}</td>
        <td>{{ $log->action }}</td>
        <td><code style="font-size:.75rem">{{ $log->path }}</code></td>
      </tr>
    @empty
      <tr><td colspan="3" class="muted" style="text-align:center;padding:1rem">فعالیتي ثبت نشده.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  var chart = @json($chart ?? ['labels'=>[],'datasets'=>[]]);
  var el = document.getElementById('myStaffChart');
  if(!el || !window.Chart) return;
  var ds = (chart.datasets||[]).map(function(d){
    return { label: d.label, data: d.profit||d.data, borderColor: d.borderColor, backgroundColor: d.backgroundColor, tension:0.25, fill:false };
  });
  new Chart(el, { type:'line', data:{ labels: chart.labels||[], datasets: ds }, options:{ responsive:true, plugins:{ legend:{ position:'bottom' } }, scales:{ y:{ beginAtZero:true } } } });
})();
</script>
@endsection
