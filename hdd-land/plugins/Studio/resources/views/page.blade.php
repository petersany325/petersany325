@extends('studio::layout')
@section('title', $title ?? 'استودیو | فراز نت آمل')
@if(!empty($description))
@section('description', $description)
@endif
@section('content')
@include('studio::bodies', ['slug' => $slug ?? 'home'])
@endsection
