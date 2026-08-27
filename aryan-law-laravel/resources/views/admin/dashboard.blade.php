@extends('layouts.admin-app')
@section('title', 'داشبورد')
@section('heading', 'داشبورد')
@section('content')
<div class="stats">
  <div class="stat">کل درخواست‌ها<b>{{ $leadsCount }}</b></div>
  <div class="stat">جدید<b>{{ $newLeads }}</b></div>
  <div class="stat">خدمات<b>{{ $servicesCount }}</b></div>
</div>
<div class="card">
  <h3 style="margin-top:0">آخرین درخواست‌ها</h3>
  <table>
    <thead><tr><th>نام</th><th>تلفن</th><th>موضوع</th><th>وضعیت</th></tr></thead>
    <tbody>
      @forelse ($latestLeads as $lead)
        <tr>
          <td>{{ $lead->name }}</td>
          <td>{{ $lead->phone }}</td>
          <td>{{ $lead->topic }}</td>
          <td>{{ $lead->status }}</td>
        </tr>
      @empty
        <tr><td colspan="4">هنوز درخواستی ثبت نشده.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
