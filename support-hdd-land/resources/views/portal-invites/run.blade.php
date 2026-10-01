@extends('layouts.app')
@section('title', 'در حال ارسال لینک | '.shop_name())
@section('page_title', 'ارسال گروهی')
@section('window_title', $batch->code)

@section('content')
<div class="panel" style="max-width:640px;margin:0 auto;">
    <h2 style="margin-top:0;">در حال ارسال… {{ $batch->code }}</h2>
    <p class="lead">{{ $batch->filterLabel() }}</p>

    <div class="emp-stat-row">
        <div class="emp-stat"><span>پیشرفت</span><strong>{{ $batch->progressPercent() }}٪</strong></div>
        <div class="emp-stat tone-ok"><span>موفق</span><strong>{{ $batch->sent_ok }}</strong></div>
        <div class="emp-stat tone-amber"><span>ناموفق</span><strong>{{ $batch->sent_fail }}</strong></div>
        <div class="emp-stat"><span>کل</span><strong>{{ $batch->cursor }} / {{ $batch->total }}</strong></div>
    </div>

    <p class="muted" style="margin-top:12px;">این صفحه به‌صورت خودکار ادامه می‌دهد تا ارسال تمام شود. صفحه را نبندید.</p>

    <form id="continue-form" method="POST" action="{{ route('portal-invites.run', $batch) }}">
        @csrf
        <button class="btn btn-primary" type="submit">ادامه دستی</button>
        <a class="btn btn-ghost" href="{{ route('portal-invites.report', ['batch_id' => $batch->id]) }}">مشاهده گزارش تا اینجا</a>
    </form>
</div>

<script>
setTimeout(function () {
    var f = document.getElementById('continue-form');
    if (f) f.submit();
}, 700);
</script>
@endsection
