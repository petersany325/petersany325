@extends('layouts.app')
@section('title', 'ویرایش مشتری | سرزمین هارد')
@section('page_title', 'ویرایش مشتری')
@section('content')
<div class="panel" style="max-width:860px;">
    <h2>ویرایش مشتری</h2>
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf @method('PUT')
        @include('customers._form')
        <div class="actions">
            <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
            <a class="btn btn-ghost" href="{{ route('customers.index') }}">بازگشت</a>
        </div>
    </form>
</div>
@endsection
