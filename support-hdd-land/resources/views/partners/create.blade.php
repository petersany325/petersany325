@extends('layouts.app')

@section('title', 'نماینده جدید | '.shop_name())
@section('page_title', 'نماینده جدید')
@section('window_title', 'همکار / نمایندگی برای ارجاع پذیرش')

@section('content')
<section class="panel" style="max-width:760px;">
    <form method="POST" action="{{ route('partners.store') }}">
        @include('partners._form')
        <div class="actions" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit">ذخیره</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">انصراف</a>
        </div>
    </form>
</section>
@endsection
