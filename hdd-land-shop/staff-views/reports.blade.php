@extends('layouts.admin')
@section('title','گزارش سود و کمیسیون کارمندان')
@section('content')
@php $nf = fn ($n) => number_format((int) $n); @endphp

<div class="vb-page">
  <div class="vb-page-head" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;align-items:center">
    <div>
      <h1>گزارش سود و کمیسیون</h1>
      <p>فروش روزانه · هزینه · سود · حاشیه٪ · کمیسیون کارمند</p>
    </div>
    <form method="get" class="row" style="gap:.4rem;align-items:end">
      <label>بازه (روز)<input type="number" name="days" value="{{ $days }}" min="1" max="365" style="width:90px"></label>
      <label>کارمند
        <select name="staff_id">
          <option value="0">همه</option>
          @foreach($staffList as $s)
            <option value="{{ $s->id }}" @selected($staffId==$s->id)>{{ $s->name }}</option>
          @endforeach
        </select>
      </label>
      <button class="btn btn-primary" type="submit">اعمال</button>
    </form>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.7rem;margin:1rem 0">
    <div class="panel" style="padding:1rem"><span class="muted">سفارش</span><strong style="display:block;font-size:1.25rem">{{ $summary['orders'] }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">فروش</span><strong style="display:block;font-size:1.25rem">{{ $nf($summary['gross']) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">هزینه</span><strong style="display:block;font-size:1.25rem">{{ $nf($summary['cost']) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">سود</span><strong style="display:block;font-size:1.25rem;color:#0f7a4b">{{ $nf($summary['profit']) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">حاشیه سود</span><strong style="display:block;font-size:1.25rem">{{ $summary['margin'] }}٪</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">کمیسیون</span><strong style="display:block;font-size:1.25rem">{{ $nf($summary['commission']) }}</strong></div>
  </div>

  <div class="panel" style="margin-bottom:1rem">
    <h3 style="margin-top:0">نمودار رشد فروش ماهانه (به نام کارمند)</h3>
    <canvas id="staffProfitChart" height="110"></canvas>
    <p class="muted" style="font-size:.85rem;margin:.5rem 0 0">هر خط = یک کارمند · محور افقی ماه · محور عمودی مبلغ فروش (تومان)</p>
  </div>

  <div class="panel" style="margin-bottom:1rem">
    <h3 style="margin-top:0">فروش روزانه</h3>
    <div style="overflow:auto">
      <table class="table">
        <thead><tr><th>تاریخ</th><th>سفارش</th><th>فروش</th><th>هزینه</th><th>سود</th><th>حاشیه</th><th>کمیسیون</th></tr></thead>
        <tbody>
        @forelse($byDay as $row)
          <tr>
            <td>{{ $row['date'] }}</td>
            <td>{{ $row['orders'] }}</td>
            <td>{{ $nf($row['gross']) }}</td>
            <td>{{ $nf($row['cost']) }}</td>
            <td>{{ $nf($row['profit']) }}</td>
            <td>{{ $row['margin'] }}٪</td>
            <td>{{ $nf($row['commission']) }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="muted" style="text-align:center;padding:1rem">داده‌ای نیست.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3 style="margin-top:0">به تفکیک کارمند</h3>
    <table class="table">
      <thead><tr><th>کارمند</th><th>٪ کمیسیون</th><th>فروش</th><th>سود</th><th>کمیسیون</th></tr></thead>
      <tbody>
      @foreach($leaderboard as $row)
        <tr>
          <td>{{ $row->staff->name }}</td>
          <td>{{ $row->staff->commission_rate }}٪</td>
          <td>{{ $nf($row->gross) }}</td>
          <td>{{ $nf($row->profit) }}</td>
          <td>{{ $nf($row->commission) }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="panel" style="margin-top:1rem">
    <div class="row" style="justify-content:space-between;align-items:center">
      <h3 style="margin:0">گزارش کار / ورود و خروج</h3>
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/staff/activity') }}">مشاهده کامل</a>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin:.8rem 0">
      @foreach(($activityCounts ?? []) as $a => $c)
        <span class="tag">{{ $a }}: {{ $c }}</span>
      @endforeach
    </div>
    <table class="table">
      <thead><tr><th>زمان</th><th>رویداد</th><th>مسیر</th><th>IP</th></tr></thead>
      <tbody>
      @forelse(($activity ?? collect())->take(40) as $log)
        <tr>
          <td>{{ substr((string)$log->created_at,0,16) }}</td>
          <td>{{ $log->action }}</td>
          <td><code style="font-size:.75rem">{{ $log->path }}</code></td>
          <td>{{ $log->ip }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted" style="text-align:center;padding:1rem">هنوز رویدادی نیست.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  var chart = @json($chart ?? ['labels'=>[],'datasets'=>[]]);
  var el = document.getElementById('staffProfitChart');
  if(!el || !window.Chart) return;
  var ds = (chart.datasets||[]).map(function(d){
    return {
      label: d.label,
      data: d.profit && d.profit.length ? d.profit : d.data,
      borderColor: d.borderColor,
      backgroundColor: d.backgroundColor,
      tension: 0.25,
      fill: false
    };
  });
  new Chart(el, {
    type: 'line',
    data: { labels: chart.labels||[], datasets: ds },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: 'بیلان رشد سود ماهانه به تفکیک نام کارمند' }
      },
      scales: { y: { beginAtZero: true } }
    }
  });
})();
</script>
@endsection
