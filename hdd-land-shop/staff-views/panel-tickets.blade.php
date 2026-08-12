@extends('layouts.staff')
@section('title','پشتیبانی')
@section('content')
<div class="row" style="justify-content:space-between;align-items:center">
  <h1 style="margin:0">تیکت‌های پشتیبانی</h1>
  <a class="btn btn-primary" href="{{ url('/admin/tickets') }}">باز کردن کارتابل کامل</a>
</div>
<div class="panel" style="overflow:auto;margin-top:.8rem">
  <table class="table">
    <thead><tr><th>کد</th><th>موضوع</th><th>وضعیت</th><th>اولویت</th><th>تاریخ</th></tr></thead>
    <tbody>
    @forelse($tickets as $t)
      <tr>
        <td>{{ $t->ticket_number ?? ('#'.$t->id) }}</td>
        <td>{{ $t->subject ?? '—' }}</td>
        <td>{{ $t->status ?? '—' }}</td>
        <td>{{ $t->priority ?? '—' }}</td>
        <td>{{ substr((string)($t->created_at ?? ''),0,16) }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted" style="text-align:center;padding:1rem">تیکتی نیست.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
