@extends('studio::layout')
@section('title', ($title ?? 'استودیو').' | سرزمین هارد')
@section('content')
@include('studio::bodies', ['slug' => $slug ?? 'home'])
@endsection
