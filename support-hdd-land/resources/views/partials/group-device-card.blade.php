@php
    $item = is_array($item ?? null) ? $item : [];
    $index = (int) ($index ?? 0);
    $serialError = $serialError ?? null;
    $hasError = filled($serialError);
    $serialValue = $item['serial_number'] ?? '';
    $previewBits = array_filter([
        $serialValue !== '' ? $serialValue : null,
        $item['brand_model'] ?? ($item['model'] ?? null),
        $item['service_type'] ?? null,
    ]);
    $preview = $previewBits !== [] ? implode(' · ', $previewBits) : 'در حال تکمیل…';
    $cardClass = 'device-card'.($hasError ? ' has-field-error is-active' : ($index === 0 ? ' is-active' : ' is-collapsed'));
@endphp
<div class="{{ $cardClass }}" data-device-card>
    <div class="device-card-head">
        <button type="button" class="device-card-toggle" data-device-toggle>
            <span class="device-index">قبض {{ $index + 1 }}</span>
            <span class="device-receipt" data-device-receipt dir="ltr">—</span>
            <span class="device-preview muted" data-device-preview>{{ $preview }}</span>
        </button>
        <div class="device-card-tools">
            <button type="button" class="btn btn-ghost" data-device-collapse title="جمع/باز">▾</button>
            <button type="button" class="btn btn-danger" data-device-remove title="حذف">×</button>
        </div>
    </div>
    <div class="device-card-body">
        <div class="dense-grid">
            <label>سریال
                <input type="text"
                       data-name="serial_number"
                       name="items[{{ $index }}][serial_number]"
                       value="{{ $serialValue }}"
                       class="{{ $hasError ? 'is-invalid' : '' }}"
                       data-barcode data-ascii-en data-fa-en
                       autocomplete="off" dir="ltr" style="text-align:left;">
                @if($hasError)
                    <div class="field-error" data-serial-error="1">{{ $serialError }}</div>
                @endif
            </label>
            <label>خدمات
                <select data-name="service_type" name="items[{{ $index }}][service_type]">
                    <option value="">—</option>
                    @foreach($serviceTypes as $name)
                        <option value="{{ $name }}" @selected(($item['service_type'] ?? '') == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label>تعمیر
                <select data-name="repair_type" name="items[{{ $index }}][repair_type]">
                    <option value="">—</option>
                    @foreach($repairTypes as $name)
                        <option value="{{ $name }}" @selected(($item['repair_type'] ?? '') == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label>برند و مدل
                <input type="text"
                       data-name="brand_model"
                       name="items[{{ $index }}][brand_model]"
                       value="{{ $item['brand_model'] ?? ($item['model'] ?? '') }}"
                       list="brand-models"
                       data-barcode data-ascii-en data-fa-en
                       autocomplete="off" dir="ltr" style="text-align:left;">
            </label>
            <label>ظرفیت
                <select data-name="hdd_capacity" name="items[{{ $index }}][hdd_capacity]">
                    <option value="">—</option>
                    @foreach($hddCapacities as $name)
                        <option value="{{ $name }}" @selected(($item['hdd_capacity'] ?? '') == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label>تعمیرکار
                <select data-name="technician_id" name="items[{{ $index }}][technician_id]">
                    <option value="">—</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" @selected((string) ($item['technician_id'] ?? '') === (string) $tech->id)>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>نوع ایراد
                <select data-name="fault_type_id" name="items[{{ $index }}][fault_type_id]">
                    <option value="">—</option>
                    @foreach($faultTypes as $fault)
                        <option value="{{ $fault->id }}" @selected((string) ($item['fault_type_id'] ?? '') === (string) $fault->id)>{{ $fault->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>گارانتی
                <select data-name="warranty_type" name="items[{{ $index }}][warranty_type]">
                    <option value="">—</option>
                    @foreach($warrantyTypes as $name)
                        <option value="{{ $name }}" @selected(($item['warranty_type'] ?? '') == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="chk">
                <input type="checkbox"
                       data-name="warranty_return"
                       name="items[{{ $index }}][warranty_return]"
                       value="1"
                       @checked(!empty($item['warranty_return']))>
                برگشت گارانتی
            </label>
            <label>بیعانه
                <input type="number" data-name="deposit" name="items[{{ $index }}][deposit]" min="0" value="{{ $item['deposit'] ?? 0 }}" data-deposit>
            </label>
            <label>تخمینی
                <input type="number" data-name="estimated_cost" name="items[{{ $index }}][estimated_cost]" min="0" value="{{ $item['estimated_cost'] ?? 0 }}">
            </label>
            <label>کارت‌خوان
                <input type="number" data-name="pos_amount" name="items[{{ $index }}][pos_amount]" min="0" value="{{ $item['pos_amount'] ?? 0 }}">
            </label>
            <label>پذیرش
                <input type="number" data-name="admission_fee" name="items[{{ $index }}][admission_fee]" min="0" value="{{ $item['admission_fee'] ?? 0 }}">
            </label>
            <label>تحویل
                <input type="text"
                       class="jalali-date"
                       data-name="estimated_delivery_at"
                       name="items[{{ $index }}][estimated_delivery_at]"
                       value="{{ $item['estimated_delivery_at'] ?? '' }}"
                       placeholder="1404/05/16"
                       dir="ltr" style="text-align:left;"
                       inputmode="numeric" autocomplete="off">
            </label>
            <label>تاریخ فروش
                <input type="text"
                       class="jalali-date"
                       data-name="sale_date"
                       name="items[{{ $index }}][sale_date]"
                       value="{{ $item['sale_date'] ?? '' }}"
                       placeholder="1404/05/16"
                       dir="ltr" style="text-align:left;"
                       inputmode="numeric" autocomplete="off">
            </label>
            <label>پایان گارانتی
                <input type="text"
                       class="jalali-date"
                       data-name="warranty_end_date"
                       name="items[{{ $index }}][warranty_end_date]"
                       value="{{ $item['warranty_end_date'] ?? '' }}"
                       placeholder="1404/05/16"
                       dir="ltr" style="text-align:left;"
                       inputmode="numeric" autocomplete="off">
            </label>
            <label>کارت گارانتی
                <input type="text" data-name="card_number" name="items[{{ $index }}][card_number]" value="{{ $item['card_number'] ?? '' }}">
            </label>
        </div>
        <div class="dense-notes">
            <div class="note-field">
                <label>عیب اظهار مشتری</label>
                <select class="note-menu" data-note-target="reported_fault" aria-label="منوی عیب اظهار مشتری">
                    <option value="">انتخاب از منو…</option>
                    @foreach($reportedFaultOptions as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
                <textarea data-name="reported_fault" name="items[{{ $index }}][reported_fault]" rows="2">{{ $item['reported_fault'] ?? '' }}</textarea>
            </div>
            <div class="note-field">
                <label>لوازم همراه</label>
                <select class="note-menu" data-note-target="accessories" aria-label="منوی لوازم همراه">
                    <option value="">انتخاب از منو…</option>
                    @foreach($accessoriesOptions as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
                <textarea data-name="accessories" name="items[{{ $index }}][accessories]" rows="2">{{ $item['accessories'] ?? 'ندارد' }}</textarea>
            </div>
            <div class="note-field">
                <label>وضعیت ظاهری</label>
                <select class="note-menu" data-note-target="appearance_notes" aria-label="منوی وضعیت ظاهری">
                    <option value="">انتخاب از منو…</option>
                    @foreach($appearanceOptions as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
                <textarea data-name="appearance_notes" name="items[{{ $index }}][appearance_notes]" rows="2">{{ $item['appearance_notes'] ?? '' }}</textarea>
            </div>
        </div>
        <div class="device-card-footer">
            <button type="button" class="btn btn-secondary" data-device-done>تأیید این قبض و ادامه</button>
            <span class="muted">بعد از تأیید، منوی قبض بعدی زیر لیست فعال می‌شود</span>
        </div>
    </div>
</div>
