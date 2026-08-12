@extends('layouts.staff')
@section('title','سریال‌ها')
@section('content')
<div class="row" style="justify-content:space-between;align-items:center">
  <h1 style="margin:0">سریال‌ها</h1>
  <a class="btn btn-primary" href="{{ url('/admin/serial-sales') }}">ثبت / فروش سریال</a>
</div>
<div class="panel" style="overflow:auto;margin-top:.8rem">
  <table class="table">
    <thead><tr><th>سریال</th><th>وضعیت</th><th>گارانتی</th><th>فروش</th></tr></thead>
    <tbody>
    @forelse($serials as $r)
      <tr>
        <td><code>{{ $r->serial }}</code></td>
        <td>{{ $r->status }}</td>
        <td>{{ $r->warranty_company ?? '—' }}</td>
        <td>{{ $r->sold_at ? substr((string)$r->sold_at,0,16) : '—' }}</td>
      </tr>
    @empty
      <tr><td colspan="4" class="muted" style="text-align:center;padding:1rem">سریالی نیست.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
