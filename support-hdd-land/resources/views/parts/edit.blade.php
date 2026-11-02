@extends('layouts.app')
@section('title', 'ویرایش کارت کالا | سرزمین هارد')
@section('page_title', 'ویرایش کارت کالا')
@section('window_title', 'ویرایش شناسنامه انبار')

@section('content')
@include('parts._nav', [
    'whTitle' => 'ویرایش: '.$part->name,
    'whSub' => 'برای تغییر موجودی از کارتکس یا رسید/حواله استفاده کنید',
    'whActions' => '<a class="btn btn-ghost" href="'.route('parts.show', $part).'">کارتکس</a>',
])
<div class="panel" style="max-width:860px;">
    <form method="POST" action="{{ route('parts.update', $part) }}">
        @csrf @method('PUT')
        @include('parts._form', ['withStock' => false])
        <div class="actions">
            <button class="btn btn-primary" type="submit">ذخیره کارت</button>
            <a class="btn btn-ghost" href="{{ route('parts.show', $part) }}">کارتکس</a>
        </div>
    </form>
</div>
@endsection
