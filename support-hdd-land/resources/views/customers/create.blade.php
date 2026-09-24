@extends('layouts.app')
@section('title', 'مشتری جدید | '.shop_name())
@section('page_title', 'ثبت مشتری')
@section('content')
<div class="panel" style="max-width:860px;">
    <h2>ثبت مشتری جدید</h2>
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        @include('customers._form')
        <div class="actions">
            <button class="btn btn-primary" type="submit">ذخیره</button>
            <a class="btn btn-ghost" href="{{ route('customers.index') }}">بازگشت</a>
        </div>
    </form>
</div>
@endsection
