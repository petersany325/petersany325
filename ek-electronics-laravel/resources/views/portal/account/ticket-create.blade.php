@extends('layouts.portal')
@section('title', 'New ticket')
@section('content')
@php($portal = 'account')
<div class="portal-hero"><h1>Open a ticket</h1><p>We will respond via the portal and WhatsApp when enabled.</p></div>
<div class="panel">
  <form class="portal-form" method="post" action="{{ route('account.tickets.store') }}">
    @csrf
    <label>Subject<input type="text" name="subject" value="{{ old('subject') }}" required>@error('subject')<span class="error">{{ $message }}</span>@enderror</label>
    <label>Department
      <select name="department" required>
        @foreach (['support'=>'Support','sales'=>'Sales','recovery'=>'Data recovery','accounts'=>'Accounts'] as $k=>$v)
          <option value="{{ $k }}" @selected(old('department')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </label>
    <label>Priority
      <select name="priority" required>
        @foreach (['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $k=>$v)
          <option value="{{ $k }}" @selected(old('priority', 'normal')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </label>
    <label>Message<textarea name="body" required>{{ old('body') }}</textarea>@error('body')<span class="error">{{ $message }}</span>@enderror</label>
    <button class="btn-portal primary" type="submit">Submit ticket</button>
  </form>
</div>
@endsection
