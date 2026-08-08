@extends('layouts.app')
@section('title', 'کالای جدید | '.shop_name())
@section('page_title', 'تعریف کالای انبار')
@section('window_title', 'کارت کالا — موجودی اول دوره')

@section('content')
@include('parts._nav', [
    'whTitle' => 'کالای جدید',
    'whSub' => 'تعریف کارت کالا؛ موجودی اولیه با سند «موجودی اول دوره» ثبت می‌شود',
])
<div class="panel" style="max-width:860px;">
    <form method="POST" action="{{ route('parts.store') }}">
        @csrf
        @include('parts._form', ['withStock' => true])
        <p class="muted" style="font-size:11.5px;">اگر موجودی اولیه وارد کنید، سند ورود انبار با بهای خرید زده می‌شود.</p>
        <div class="actions">
            <button class="btn btn-primary" type="submit">ثبت در انبار</button>
            <a class="btn btn-ghost" href="{{ route('parts.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
