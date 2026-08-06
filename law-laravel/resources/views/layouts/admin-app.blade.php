@extends('layouts.admin')
@section('title', $title ?? 'پنل مدیریت')
@section('body')
<div class="shell">
  <aside class="side">
    <h1>پنل مدیریت</h1>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">داشبورد</a>
    <a href="{{ route('admin.leads.index') }}" class="{{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">درخواست‌ها</a>
    <a href="{{ route('admin.services.index') }}" class="{{ request()->routeIs('admin.services.*') ? 'active' : '' }}">خدمات</a>
    <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">تنظیمات سایت</a>
    <a href="{{ route('home') }}" target="_blank">مشاهده سایت</a>
    <form method="post" action="{{ route('admin.logout') }}" style="margin-top:1rem">
      @csrf
      <button class="btn btn-dark" type="submit" style="width:100%">خروج</button>
    </form>
  </aside>
  <main class="main">
    <div class="top">
      <h2 style="margin:0">@yield('heading')</h2>
      <div>{{ auth()->user()->name }}</div>
    </div>
    @if (session('success'))
      <div class="alert">{{ session('success') }}</div>
    @endif
    @yield('content')
  </main>
</div>
@endsection
