@extends('layouts.app')
@section('title', 'ورود از اکسل | '.shop_name())
@section('page_title', 'ورود کالا / قیمت / موجودی از فایل')
@section('content')
<div class="panel">
    <h2>ورود از اکسل / CSV</h2>
    <p class="lead">ستون‌ها: name, code, tech_code, barcode, brand, model, stock, purchase_price, sale_price, min_stock, keywords, description, item_type, discount_percent</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('parts.import.store') }}" enctype="multipart/form-data" class="accept-row accept-row-3" style="align-items:end;">
        @csrf
        <div><label>فایل CSV/Excel</label><input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required></div>
        <div>
            <label>انبار پیش‌فرض</label>
            <select name="warehouse_id">
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>@include('partials.toggle', ['name' => 'update_existing', 'label' => 'به‌روزرسانی کالاهای موجود', 'checked' => true])</div>
        <div><button class="btn btn-primary" type="submit">بارگذاری</button></div>
    </form>

    <h3 style="margin-top:18px;">تغییر سریع قیمت (چند کلیک)</h3>
    <form method="POST" action="{{ route('parts.bulk-prices') }}" class="accept-row accept-row-4" style="align-items:end;" data-confirm="قیمت‌ها تغییر کند؟">
        @csrf
        <div><label>درصد (+/-)</label><input type="number" step="0.1" name="percent" value="10" required></div>
        <div>
            <label>فیلد</label>
            <select name="field">
                <option value="sale_price">فی فروش</option>
                <option value="purchase_price">بهای خرید</option>
            </select>
        </div>
        <div>
            <label>انبار</label>
            <select name="warehouse_id"><option value="">همه</option>
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label>نوع</label>
            <select name="item_type"><option value="">همه</option>
                <option value="shop">فروشگاه</option>
                <option value="repair">قطعه تعمیر</option>
                <option value="labor">اجرت</option>
            </select>
        </div>
        <div><button class="btn btn-secondary" type="submit">اعمال درصد</button></div>
    </form>
</div>
@endsection
