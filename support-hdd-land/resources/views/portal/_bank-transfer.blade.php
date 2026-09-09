@php
    $bank = \App\Support\BankTransferSettings::all();
    $enabled = \App\Support\BankTransferSettings::isEnabled();
    $remaining = (int) $reception->remainingAmount();
    $receipts = $receipts ?? collect();
@endphp

@if($enabled && $remaining >= 1000 && $reception->status !== 'cancelled')
<section class="p-section">
    <h2>واریز کارت‌به‌کارت</h2>
    <div class="p-ready-banner" style="margin-bottom:10px;">
        <strong>{{ $bank['bank_name'] ?: 'کارت شرکت' }}</strong>
        <p style="margin:6px 0 0;font-size:1.15rem;letter-spacing:.04em;" dir="ltr">{{ \App\Support\BankTransferSettings::formattedCard($bank['card_number']) }}</p>
        @if($bank['card_holder'])
            <p style="margin:4px 0 0;">به نام: {{ $bank['card_holder'] }}</p>
        @endif
        @if($bank['iban'])
            <p style="margin:4px 0 0;" dir="ltr">شبا: {{ $bank['iban'] }}</p>
        @endif
        @if($bank['instructions'])
            <p style="margin:8px 0 0;font-size:.9rem;">{{ $bank['instructions'] }}</p>
        @endif
        <p style="margin:8px 0 0;">مانده قابل پرداخت: {{ number_format($remaining) }} تومان</p>
    </div>

    @php
        $hasPending = $receipts->contains(fn ($r) => $r->status === 'pending');
    @endphp

    @if($hasPending)
        <div class="p-empty soft">یک فیش در انتظار تأیید حسابداری دارید. تا بررسی، فیش جدید ارسال نکنید.</div>
    @else
        <form method="POST" action="{{ route('portal.receipts.store', $reception) }}" enctype="multipart/form-data" id="portal-bank-receipt-form">
            @csrf
            <div style="display:grid;gap:8px;">
                <label>
                    مبلغ واریزی (تومان)
                    <input type="number" name="amount" value="{{ old('amount', $remaining) }}" min="1000" max="{{ $remaining }}" required>
                </label>
                <label>
                    تاریخ واریز
                    @include('partials.jalali-date', ['name' => 'transfer_date', 'value' => old('transfer_date', jalali_input(now())), 'required' => true])
                </label>
                <div>
                    <label style="display:block;margin-bottom:6px;">تصویر فیش بانکی</label>
                    <div style="display:grid;gap:8px;">
                        <input type="file"
                               id="receipt-image-input"
                               name="receipt_image"
                               accept="image/*,image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif,application/pdf"
                               required>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="button" class="p-btn" id="receipt-camera-btn">عکس با دوربین</button>
                            <button type="button" class="p-btn" id="receipt-gallery-btn">انتخاب از گالری</button>
                        </div>
                        <input type="file"
                               id="receipt-camera-input"
                               accept="image/*"
                               capture="environment"
                               style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;"
                               tabindex="-1"
                               aria-hidden="true">
                        <p class="p-empty soft" id="receipt-file-name" style="margin:0;">هنوز فایلی انتخاب نشده. JPG / PNG / WEBP / PDF (و HEIC در صورت پشتیبانی گوشی).</p>
                    </div>
                    @error('receipt_image')
                        <p class="p-empty soft" style="color:#9f1239;margin:6px 0 0;">{{ $message }}</p>
                    @enderror
                </div>
                <label>
                    توضیح (اختیاری)
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="500" placeholder="مثلاً ۴ رقم آخر کارت مبدأ">
                </label>
                <button class="p-btn primary" type="submit">ارسال فیش برای تأیید</button>
            </div>
            <p class="p-empty soft" style="margin-top:8px;">تا تأیید مدیر/حسابدار، این واریز در گزارش مالی قطعی نیست.</p>
        </form>
        <script>
        (function () {
            var form = document.getElementById('portal-bank-receipt-form');
            if (!form) return;
            var main = document.getElementById('receipt-image-input');
            var cam = document.getElementById('receipt-camera-input');
            var nameEl = document.getElementById('receipt-file-name');
            var camBtn = document.getElementById('receipt-camera-btn');
            var galBtn = document.getElementById('receipt-gallery-btn');
            function showName(file) {
                if (!nameEl) return;
                nameEl.textContent = file ? ('انتخاب شد: ' + file.name) : 'هنوز فایلی انتخاب نشده.';
            }
            function syncFrom(input) {
                if (!input || !input.files || !input.files[0] || !main) return;
                try {
                    var dt = new DataTransfer();
                    dt.items.add(input.files[0]);
                    main.files = dt.files;
                    showName(input.files[0]);
                } catch (e) {
                    // Fallback: some browsers block DataTransfer — keep camera input name.
                    if (input !== main) {
                        input.setAttribute('name', 'receipt_image');
                        main.removeAttribute('name');
                        main.removeAttribute('required');
                        input.required = true;
                    }
                    showName(input.files[0]);
                }
            }
            if (camBtn && cam) camBtn.addEventListener('click', function () { cam.click(); });
            if (galBtn && main) galBtn.addEventListener('click', function () { main.click(); });
            if (cam) cam.addEventListener('change', function () { syncFrom(cam); });
            if (main) main.addEventListener('change', function () { showName(main.files && main.files[0]); });
        })();
        </script>
    @endif
</section>
@endif

@if(($receipts ?? collect())->count())
<section class="p-section">
    <h2>فیش‌های ارسالی</h2>
    <div class="p-parts">
        @foreach($receipts as $receipt)
            <div class="p-part-row">
                <div>
                    <strong>{{ $receipt->statusLabel() }} — {{ number_format((int) $receipt->amount) }} تومان</strong>
                    <small>
                        {{ jalali_like($receipt->created_at) }}
                        @if($receipt->review_note && $receipt->status === 'rejected')
                            — {{ $receipt->review_note }}
                        @endif
                    </small>
                </div>
                <span>
                    @if($receipt->hasImage())
                        <a href="{{ route('portal.receipts.image', $receipt) }}" target="_blank">مشاهده</a>
                    @else
                        —
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</section>
@endif
