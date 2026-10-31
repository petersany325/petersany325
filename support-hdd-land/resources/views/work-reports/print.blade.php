<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>گزارش شرح کارها</title>
    <style>
        body{font-family:Tahoma,sans-serif;font-size:12px;padding:16px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #ccc;padding:6px;text-align:right}
        th{background:#f3f4f6}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<button class="no-print" onclick="window.print()">چاپ</button>
<h2>گزارش شرح کارها @if($q) — جستجو: {{ $q }}@endif</h2>
<table>
    <thead><tr><th>تاریخ</th><th>قبض</th><th>نویسنده</th><th>سطح</th><th>خلاصه</th><th>جزئیات</th></tr></thead>
    <tbody>
    @foreach($reports as $r)
        <tr>
            <td>{{ jalali_like($r->created_at) }}</td>
            <td>{{ $r->reception?->ticket_no }}</td>
            <td>{{ $r->technician?->name ?: $r->user?->name }}</td>
            <td>{{ $r->visibilityLabel() }}</td>
            <td>{{ $r->summary }}</td>
            <td>{{ $r->details }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<script>window.addEventListener('load',function(){window.print()});</script>
</body>
</html>
