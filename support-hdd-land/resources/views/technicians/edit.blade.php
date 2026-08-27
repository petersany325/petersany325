@extends('layouts.app')
@section('title', 'ویرایش تعمیرکار | سرزمین هارد')
@section('page_title', 'ویرایش تعمیرکار')
@section('content')
<div class="panel" style="max-width:860px;">
    <h2>ویرایش تعمیرکار</h2>
    <form method="POST" action="{{ route('technicians.update', $technician) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div><label>نام</label><input type="text" name="name" value="{{ old('name', $technician->name) }}" required></div>
            <div><label>تلفن</label><input type="text" name="phone" value="{{ old('phone', $technician->phone) }}"></div>
            <div><label>تخصص</label><input type="text" name="specialty" value="{{ old('specialty', $technician->specialty) }}"></div>
            <div><label>کمیسیون %</label><input type="number" name="commission_percent" min="0" max="100" value="{{ old('commission_percent', $technician->commission_percent) }}"></div>
            <div>
                @include('partials.toggle', [
                    'name' => 'is_active',
                    'label' => 'وضعیت تعمیرکار',
                    'checked' => (bool) old('is_active', $technician->is_active),
                    'on' => 'فعال',
                    'off' => 'غیرفعال',
                ])
            </div>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit">ذخیره</button></div>
    </form>
</div>
@endsection
