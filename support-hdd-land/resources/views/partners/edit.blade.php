@extends('layouts.app')

@section('title', 'ویرایش نماینده | '.shop_name())
@section('page_title', 'ویرایش نماینده')
@section('window_title', $partner->name)

@section('content')
<section class="panel" style="max-width:760px;">
    <form method="POST" action="{{ route('partners.update', $partner) }}">
        @method('PUT')
        @include('partners._form')
        <div class="actions" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit">ذخیره</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">انصراف</a>
        </div>
    </form>
</section>
@endsection
