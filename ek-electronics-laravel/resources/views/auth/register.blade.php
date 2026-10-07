@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="auth-card">
  <h1>Create customer account</h1>
  <p class="blurb">{{ \App\Models\Setting::getValue('customer_register_blurb', 'Create a free customer account to follow purchases and recovery tickets.') }}</p>
  <form class="portal-form" method="post" action="{{ route('register.submit') }}">
    @csrf
    <label>Full name
      <input type="text" name="name" value="{{ old('name') }}" required>
      @error('name')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Email
      <input type="email" name="email" value="{{ old('email') }}" required>
      @error('email')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Phone {{ \App\Models\Setting::bool('require_phone_on_register', false) ? '' : '(optional)' }}
      <input type="tel" name="phone" value="{{ old('phone') }}" @if(\App\Models\Setting::bool('require_phone_on_register', false)) required @endif>
      @error('phone')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Password
      <input type="password" name="password" required>
      @error('password')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Confirm password
      <input type="password" name="password_confirmation" required>
    </label>
    <button class="btn-portal primary" type="submit" style="width:100%">Register</button>
  </form>
  <p class="auth-links">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</div>
@endsection
