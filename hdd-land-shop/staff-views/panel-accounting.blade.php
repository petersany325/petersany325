@extends('layouts.staff')
@section('title','حسابداری')
@section('content')
@php $nf = fn ($n) => number_format((int) $n); @endphp
<h1>حسابداری</h1>
<p class="muted">خلاصه مالی {{ $days }} روز اخیر</p>
<div class="staff-grid">
  <div class="staff-stat"><span class="muted">فروش کل</span><strong>{{ $nf($summary['gross']) }}</strong></div>
  <div class="staff-stat"><span class="muted">هزینه کالا</span><strong>{{ $nf($summary['cost']) }}</strong></div>
  <div class="staff-stat"><span class="muted">سود</span><strong style="color:#0f7a4b">{{ $nf($summary['profit']) }}</strong></div>
  <div class="staff-stat"><span class="muted">حاشیه</span><strong>{{ $summary['margin'] }}٪</strong></div>
  <div class="staff-stat"><span class="muted">پرداخت‌شده</span><strong>{{ $nf($paid) }}</strong></div>
  <div class="staff-stat"><span class="muted">پرداخت‌نشده</span><strong>{{ $nf($unpaid) }}</strong></div>
</div>
<div class="panel" style="margin-top:1rem;overflow:auto">
  <table class="table">
    <thead><tr><th>تاریخ</th><th>سفارش</th><th>فروش</th><th>هزینه</th><th>سود</th><th>حاشیه</th></tr></thead>
    <tbody>
    @foreach($byDay as $row)
      <tr>
        <td>{{ $row['date'] }}</td>
        <td>{{ $row['orders'] }}</td>
        <td>{{ $nf($row['gross']) }}</td>
        <td>{{ $nf($row['cost']) }}</td>
        <td>{{ $nf($row['profit']) }}</td>
        <td>{{ $row['margin'] }}٪</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
