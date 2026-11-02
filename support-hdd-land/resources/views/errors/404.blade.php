@extends('errors.minimal')

@section('title', 'صفحه پیدا نشد')
@section('code', '404')
@section('message', 'این صفحه پیدا نشد یا حذف شده است.')
@section('action')
    <a href="{{ url('/') }}">بازگشت به صفحه اصلی</a>
@endsection
