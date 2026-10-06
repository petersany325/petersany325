@csrf
<div class="accept-row accept-row-2">
    <div>
        <label>نام رابط / نماینده *</label>
        <input type="text" name="name" value="{{ old('name', $partner->name ?? '') }}" required>
    </div>
    <div>
        <label>نام فروشگاه / نمایندگی</label>
        <input type="text" name="shop_name" value="{{ old('shop_name', $partner->shop_name ?? '') }}" placeholder="اختیاری">
    </div>
    <div>
        <label>موبایل / تلفن</label>
        <input type="text" name="phone" value="{{ old('phone', $partner->phone ?? '') }}" dir="ltr" style="text-align:left;">
    </div>
    <div>
        <label>کد / شناسه داخلی</label>
        <input type="text" name="code" value="{{ old('code', $partner->code ?? '') }}" placeholder="اختیاری" dir="ltr" style="text-align:left;">
    </div>
    <div style="grid-column:1/-1;">
        <label>یادداشت</label>
        <textarea name="notes" rows="2">{{ old('notes', $partner->notes ?? '') }}</textarea>
    </div>
    <div>
        <label class="inline" style="display:inline-flex;align-items:center;gap:8px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $partner->is_active ?? true))>
            فعال
        </label>
    </div>
</div>
