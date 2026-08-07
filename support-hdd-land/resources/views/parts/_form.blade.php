<div class="form-grid">
    <div><label>کد کالا</label><input type="text" name="code" value="{{ old('code', $part->code ?? '') }}" data-ascii-en placeholder="مثلاً HDD-PCB-01"></div>
    <div><label>نام کالا</label><input type="text" name="name" value="{{ old('name', $part->name ?? '') }}" required></div>
    <div><label>برند</label><input type="text" name="brand" value="{{ old('brand', $part->brand ?? '') }}"></div>
    <div><label>مدل / سازگاری</label><input type="text" name="model" value="{{ old('model', $part->model ?? '') }}"></div>
    @if(!empty($withStock))
        <div><label>موجودی اولیه</label><input type="number" name="stock" min="0" value="{{ old('stock', $part->stock ?? 0) }}"></div>
    @endif
    <div><label>نقطه سفارش (حداقل)</label><input type="number" name="min_stock" min="0" value="{{ old('min_stock', $part->min_stock ?? 0) }}"></div>
    <div><label>بهای خرید (تومان)</label><input type="number" name="purchase_price" min="0" value="{{ old('purchase_price', $part->purchase_price ?? 0) }}"></div>
    <div><label>فی فروش به مشتری</label><input type="number" name="sale_price" min="0" value="{{ old('sale_price', $part->sale_price ?? 0) }}"></div>
    <div>
        @include('partials.toggle', [
            'name' => 'is_active',
            'label' => 'وضعیت کالا در انبار',
            'checked' => (bool) old('is_active', $part->is_active ?? true),
            'on' => 'فعال',
            'off' => 'غیرفعال',
        ])
    </div>
</div>
