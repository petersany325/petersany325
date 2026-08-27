@extends('layouts.admin-app')
@section('title', 'خدمات')
@section('heading', 'مدیریت خدمات')
@section('content')
<div class="card">
  <h3 style="margin-top:0">افزودن خدمت</h3>
  <form method="post" action="{{ route('admin.services.store') }}">
    @csrf
    <label>عنوان<input name="title" required></label>
    <label>توضیح<textarea name="description" rows="3"></textarea></label>
    <label>ترتیب<input type="number" name="sort_order" value="0"></label>
    <button class="btn" type="submit">افزودن</button>
  </form>
</div>

@foreach ($services as $service)
  <div class="card">
    <form method="post" action="{{ route('admin.services.update', $service) }}">
      @csrf @method('PUT')
      <label>عنوان<input name="title" value="{{ $service->title }}" required></label>
      <label>توضیح<textarea name="description" rows="3">{{ $service->description }}</textarea></label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <label>ترتیب<input type="number" name="sort_order" value="{{ $service->sort_order }}"></label>
        <label style="display:flex;align-items:center;gap:.5rem;margin-top:1.5rem">
          <input type="checkbox" name="is_active" value="1" style="width:auto" @checked($service->is_active)> فعال
        </label>
      </div>
      <button class="btn" type="submit">ذخیره</button>
    </form>
    <form method="post" action="{{ route('admin.services.destroy', $service) }}" style="margin-top:.75rem" onsubmit="return confirm('حذف شود؟')">
      @csrf @method('DELETE')
      <button class="btn btn-danger" type="submit">حذف</button>
    </form>
  </div>
@endforeach
@endsection
