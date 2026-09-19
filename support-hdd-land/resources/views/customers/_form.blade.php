<div class="form-grid">
    <div>
        <label>نام (یکتا)</label>
        <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required maxlength="120" placeholder="نام کامل مشتری">
        @error('name')<div class="hint" style="color:#9f1239;">{{ $message }}</div>@enderror
    </div>
    <div>
        <label>موبایل (یکتا)</label>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" required maxlength="20" placeholder="09xxxxxxxxx" dir="ltr" style="text-align:left;" data-ascii-en>
        @error('phone')<div class="hint" style="color:#9f1239;">{{ $message }}</div>@enderror
    </div>
    <div>
        <label>کد ملی</label>
        <input type="text" name="national_code" value="{{ old('national_code', $customer->national_code ?? '') }}">
    </div>
    <div>
        <label>شغل</label>
        <input type="text" name="job" value="{{ old('job', $customer->job ?? '') }}">
    </div>
    <div>
        <label>نحوه آشنایی</label>
        <select name="referral_source_id">
            <option value="">—</option>
            @foreach($referralSources as $source)
                <option value="{{ $source->id }}" @selected(old('referral_source_id', $customer->referral_source_id ?? '') == $source->id)>{{ $source->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="full">
        <label>آدرس</label>
        <input type="text" name="address" value="{{ old('address', $customer->address ?? '') }}">
    </div>
    <div class="full">
        <label>یادداشت</label>
        <textarea name="notes">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>
</div>
