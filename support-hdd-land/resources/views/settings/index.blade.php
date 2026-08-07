@extends('layouts.app')
@section('title', 'تنظیمات | سرزمین هارد')
@section('page_title', 'تنظیمات')
@section('window_title', 'پنجره تنظیمات سیستم')
@section('content')
@php($activeTab = $activeTab ?? 'lookups')
<div data-workspace-tabs data-active-tab="{{ $activeTab }}">
    <div class="ws-tabs">
        <button type="button" class="{{ $activeTab === 'lookups' ? 'active' : '' }}" data-ws-tab="lookups">منوهای پذیرش</button>
        <button type="button" class="{{ $activeTab === 'faults' ? 'active' : '' }}" data-ws-tab="faults">انواع ایراد</button>
        <button type="button" class="{{ $activeTab === 'referrals' ? 'active' : '' }}" data-ws-tab="referrals">نحوه آشنایی</button>
        <button type="button" class="{{ $activeTab === 'invoice' ? 'active' : '' }}" data-ws-tab="invoice">فاکتور / چاپ</button>
        <button type="button" class="{{ $activeTab === 'payments' ? 'active' : '' }}" data-ws-tab="payments">پرداخت / زرین‌پال</button>
        <button type="button" class="{{ $activeTab === 'sms' ? 'active' : '' }}" data-ws-tab="sms">پیامک نیازپرداز</button>
        <button type="button" class="{{ $activeTab === 'backup' ? 'active' : '' }}" data-ws-tab="backup">بکاپ دیتابیس</button>
        <button type="button" class="{{ $activeTab === 'users' ? 'active' : '' }}" data-ws-tab="users">کارتابل کارمند</button>
    </div>
    <div class="ws-panes">
        <div class="ws-pane {{ $activeTab === 'lookups' ? 'active' : '' }}" data-ws-pane="lookups">
            <h2>منوهای پذیرش</h2>
            <p class="lead">از لیست کشویی مورد را انتخاب کنید، سپس ویرایش، فعال/غیرفعال یا حذف کنید. برای مورد جدید، «— جدید —» را بگذارید.</p>

            @foreach($lookups as $groupKey => $group)
                <div class="lookup-editor panel" data-lookup-editor data-base-url="{{ url('settings/lookups') }}">
                    <h3>{{ $group['label'] }}</h3>
                    <div class="accept-row accept-row-4" style="margin-bottom:6px;">
                        <div style="grid-column:1/-1">
                            <label>انتخاب از منو</label>
                            <select class="lookup-select">
                                <option value="">— جدید —</option>
                                @foreach($group['items'] as $item)
                                    <option value="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            data-sort="{{ $item->sort_order }}"
                                            data-active="{{ $item->is_active ? '1' : '0' }}">
                                        {{ $item->name }}{{ $item->is_active ? '' : ' (غیرفعال)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.lookups') }}" class="lookup-form-create">
                        @csrf
                        <input type="hidden" name="settings_tab" value="lookups">
                        <input type="hidden" name="group_key" value="{{ $groupKey }}">
                        <div class="accept-row accept-row-4">
                            <div>
                                <label>نام گزینه</label>
                                <input type="text" name="name" class="lookup-name" required placeholder="نام گزینه">
                            </div>
                            <div>
                                <label>ترتیب</label>
                                <input type="number" name="sort_order" class="lookup-sort" min="0" value="0">
                            </div>
                            <div>
                                @include('partials.toggle', [
                                    'name' => 'is_active',
                                    'label' => 'فعال در منوی پذیرش',
                                    'checked' => true,
                                ])
                            </div>
                        </div>
                        <div class="actions lookup-actions-create">
                            <button class="btn btn-primary" type="submit">افزودن به منو</button>
                        </div>
                    </form>

                    <form method="POST" action="#" class="lookup-form-edit hidden">
                        @csrf
                        <input type="hidden" name="settings_tab" value="lookups">
                        <input type="hidden" name="_method" value="PUT" class="lookup-method">
                        <div class="accept-row accept-row-4">
                            <div>
                                <label>نام گزینه</label>
                                <input type="text" name="name" class="lookup-name" required>
                            </div>
                            <div>
                                <label>ترتیب</label>
                                <input type="number" name="sort_order" class="lookup-sort" min="0" value="0">
                            </div>
                            <div>
                                @include('partials.toggle', [
                                    'name' => 'is_active',
                                    'label' => 'فعال در منوی پذیرش',
                                    'checked' => true,
                                ])
                            </div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-primary lookup-save-btn" type="submit">ذخیره ویرایش</button>
                            <button class="btn btn-danger lookup-delete-btn" type="submit" data-confirm="حذف این گزینه؟">حذف</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="ws-pane {{ $activeTab === 'faults' ? 'active' : '' }}" data-ws-pane="faults">
            <h2>انواع ایراد</h2>
            <p class="lead">از لیست انتخاب کنید، سپس ویرایش یا حذف کنید. برای مورد جدید «— جدید —» را بگذارید.</p>
            <div class="lookup-editor panel" data-simple-editor data-base-url="{{ url('settings/fault-types') }}">
                <div class="accept-row accept-row-4" style="margin-bottom:6px;">
                    <div style="grid-column:1/-1">
                        <label>انتخاب از منو</label>
                        <select class="simple-select">
                            <option value="">— جدید —</option>
                            @foreach($faultTypes as $item)
                                <option value="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-active="{{ $item->is_active ? '1' : '0' }}">
                                    {{ $item->name }}{{ $item->is_active ? '' : ' (غیرفعال)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <form method="POST" action="{{ route('settings.fault-types') }}" class="simple-form-create">
                    @csrf
                    <input type="hidden" name="settings_tab" value="faults">
                    <div class="accept-row accept-row-4">
                        <div>
                            <label>نام ایراد</label>
                            <input type="text" name="name" class="simple-name" required>
                        </div>
                        <div>
                            @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال', 'checked' => true])
                        </div>
                    </div>
                    <div class="actions simple-actions-create">
                        <button class="btn btn-primary" type="submit">افزودن</button>
                    </div>
                </form>
                <form method="POST" action="#" class="simple-form-edit hidden">
                    @csrf
                    <input type="hidden" name="settings_tab" value="faults">
                    <input type="hidden" name="_method" value="PUT" class="simple-method">
                    <div class="accept-row accept-row-4">
                        <div>
                            <label>نام ایراد</label>
                            <input type="text" name="name" class="simple-name" required>
                        </div>
                        <div>
                            @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال', 'checked' => true])
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary simple-save-btn" type="submit">ذخیره ویرایش</button>
                        <button class="btn btn-danger simple-delete-btn" type="submit" data-confirm="حذف این نوع ایراد؟">حذف</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="ws-pane {{ $activeTab === 'referrals' ? 'active' : '' }}" data-ws-pane="referrals">
            <h2>نحوه آشنایی</h2>
            <p class="lead">از لیست انتخاب کنید، سپس ویرایش یا حذف کنید.</p>
            <div class="lookup-editor panel" data-simple-editor data-base-url="{{ url('settings/referral-sources') }}">
                <div class="accept-row accept-row-4" style="margin-bottom:6px;">
                    <div style="grid-column:1/-1">
                        <label>انتخاب از منو</label>
                        <select class="simple-select">
                            <option value="">— جدید —</option>
                            @foreach($referralSources as $item)
                                <option value="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-active="{{ $item->is_active ? '1' : '0' }}">
                                    {{ $item->name }}{{ $item->is_active ? '' : ' (غیرفعال)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <form method="POST" action="{{ route('settings.referral-sources') }}" class="simple-form-create">
                    @csrf
                    <input type="hidden" name="settings_tab" value="referrals">
                    <div class="accept-row accept-row-4">
                        <div>
                            <label>نام منبع</label>
                            <input type="text" name="name" class="simple-name" required>
                        </div>
                        <div>
                            @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال', 'checked' => true])
                        </div>
                    </div>
                    <div class="actions simple-actions-create">
                        <button class="btn btn-primary" type="submit">افزودن</button>
                    </div>
                </form>
                <form method="POST" action="#" class="simple-form-edit hidden">
                    @csrf
                    <input type="hidden" name="settings_tab" value="referrals">
                    <input type="hidden" name="_method" value="PUT" class="simple-method">
                    <div class="accept-row accept-row-4">
                        <div>
                            <label>نام منبع</label>
                            <input type="text" name="name" class="simple-name" required>
                        </div>
                        <div>
                            @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال', 'checked' => true])
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary simple-save-btn" type="submit">ذخیره ویرایش</button>
                        <button class="btn btn-danger simple-delete-btn" type="submit" data-confirm="حذف این نحوه آشنایی؟">حذف</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="ws-pane {{ $activeTab === 'invoice' ? 'active' : '' }}" data-ws-pane="invoice">
            <h2>تنظیمات فاکتور و چاپ</h2>
            <p class="lead">اطلاعات سربرگ فاکتور، نمایش فیلدها، چاپ خودکار و شرایط تعمیرات را اینجا تنظیم کنید.</p>
            <form method="POST" action="{{ route('settings.invoice') }}">
                @csrf
                <input type="hidden" name="settings_tab" value="invoice">
                <div class="accept-row accept-row-2">
                    <div>
                        <label>نام فروشگاه / برند روی فاکتور</label>
                        <input type="text" name="invoice_shop_name" value="{{ old('invoice_shop_name', $invoice['shop_name']) }}">
                    </div>
                    <div>
                        <label>تلفن‌های تماس (نمایش روی فاکتور)</label>
                        <input type="text" name="invoice_phones" value="{{ old('invoice_phones', $invoice['phones']) }}" placeholder="021-... | 0912-...">
                    </div>
                    <div style="grid-column:1/-1">
                        <label>آدرس</label>
                        <input type="text" name="invoice_address" value="{{ old('invoice_address', $invoice['address']) }}">
                    </div>
                    <div style="grid-column:1/-1">
                        <label>متن زیر عنوان / فوتر کوتاه</label>
                        <input type="text" name="invoice_footer" value="{{ old('invoice_footer', $invoice['footer']) }}">
                    </div>
                </div>

                <h3 style="margin-top:12px;">تنظیمات صفحه چاپ</h3>
                <div class="accept-row accept-row-4">
                    <div>
                        <label>اندازه فونت (pt)</label>
                        <input type="number" name="invoice_font_size" min="8" max="18" value="{{ old('invoice_font_size', $invoice['font_size'] ?? 11) }}">
                    </div>
                    <div>
                        <label>اندازه کاغذ</label>
                        <select name="invoice_page_size">
                            @foreach(['A4' => 'A4', 'A5' => 'A5', 'Letter' => 'Letter'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('invoice_page_size', $invoice['page_size'] ?? 'A4') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>حاشیه چاپ (mm)</label>
                        <input type="number" name="invoice_margin_mm" min="4" max="25" value="{{ old('invoice_margin_mm', $invoice['margin_mm'] ?? 10) }}">
                    </div>
                    <div>
                        @include('partials.toggle', ['name' => 'invoice_show_logo', 'label' => 'نمایش لوگوی HDD LAND', 'checked' => $invoice['show_logo'] ?? true])
                    </div>
                </div>

                <h3 style="margin-top:12px;">نمایش فیلدها روی فاکتور</h3>
                <div class="toggle-grid">
                    @include('partials.toggle', ['name' => 'invoice_show_serial', 'label' => 'سریال دستگاه', 'checked' => $invoice['show_serial']])
                    @include('partials.toggle', ['name' => 'invoice_show_fault', 'label' => 'عیب اظهار مشتری', 'checked' => $invoice['show_fault']])
                    @include('partials.toggle', ['name' => 'invoice_show_accessories', 'label' => 'لوازم همراه', 'checked' => $invoice['show_accessories']])
                    @include('partials.toggle', ['name' => 'invoice_show_appearance', 'label' => 'وضعیت ظاهری', 'checked' => $invoice['show_appearance']])
                    @include('partials.toggle', ['name' => 'invoice_show_warranty', 'label' => 'گارانتی', 'checked' => $invoice['show_warranty']])
                    @include('partials.toggle', ['name' => 'invoice_show_technician', 'label' => 'تعمیرکار', 'checked' => $invoice['show_technician']])
                    @include('partials.toggle', ['name' => 'invoice_show_deposit', 'label' => 'بیعانه', 'checked' => $invoice['show_deposit']])
                    @include('partials.toggle', ['name' => 'invoice_show_estimated_cost', 'label' => 'هزینه تخمینی', 'checked' => $invoice['show_estimated_cost']])
                    @include('partials.toggle', ['name' => 'invoice_show_parts', 'label' => 'جدول قطعات', 'checked' => $invoice['show_parts']])
                    @include('partials.toggle', ['name' => 'invoice_show_payments', 'label' => 'جمع / پرداخت / مانده', 'checked' => $invoice['show_payments']])
                    @include('partials.toggle', ['name' => 'invoice_auto_print', 'label' => 'چاپ خودکار هنگام باز شدن فاکتور', 'checked' => $invoice['auto_print']])
                </div>

                <h3 style="margin-top:12px;">شرایط تعمیرات و قوانین</h3>
                <p class="lead">این متن پایین فاکتور چاپ می‌شود (شرایط پذیرش، گارانتی تعمیر، مسئولیت داده‌ها و …).</p>
                <div>
                    <label>متن شرایط</label>
                    <textarea name="invoice_terms" rows="8" placeholder="مثال: مسئولیت نگهداری اطلاعات با مشتری است. دستگاه پس از ۳۰ روز بلاتکلیف به انبار امانی منتقل می‌شود...">{{ old('invoice_terms', $invoice['terms']) }}</textarea>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">ذخیره تنظیمات فاکتور</button>
                </div>
            </form>
        </div>

        <div class="ws-pane {{ $activeTab === 'payments' ? 'active' : '' }}" data-ws-pane="payments">
            <h2>درگاه پرداخت و لینک بانک‌ها</h2>
            <p class="lead">زرین‌پال به‌صورت IPG واقعی فعال است. بانک‌ها بعد از گرفتن اطلاعات ترمینال اضافه می‌شوند.</p>
            <div class="panel" style="margin-bottom:10px;">
                <strong>وب‌سرویس مشتری:</strong>
                <div dir="ltr" style="margin-top:4px;"><a href="{{ $portalUrl }}" target="_blank">{{ $portalUrl }}</a></div>
            </div>
            <form method="POST" action="{{ route('settings.payments') }}">
                @csrf
                <input type="hidden" name="settings_tab" value="payments">
                <h3>زرین‌پال (IPG)</h3>
                <div class="pay-settings-grid">
                    <div class="pay-settings-card tone-amber">
                        <label for="zarinpal_merchant_id">Merchant ID</label>
                        <small>کد ۳۶ کاراکتری پنل زرین‌پال</small>
                        <input id="zarinpal_merchant_id" type="text" name="zarinpal_merchant_id"
                               value="{{ old('zarinpal_merchant_id', $zarinpal['merchant_id']) }}"
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" dir="ltr">
                    </div>
                    <div class="pay-settings-card tone-amber">
                        <label for="zarinpal_currency">واحد مبلغ</label>
                        <small>IRT = تومان (پیشنهادی) · IRR = ریال</small>
                        <select id="zarinpal_currency" name="zarinpal_currency">
                            <option value="IRT" @selected(old('zarinpal_currency', $zarinpal['currency']) === 'IRT')>IRT — تومان</option>
                            <option value="IRR" @selected(old('zarinpal_currency', $zarinpal['currency']) === 'IRR')>IRR — ریال</option>
                        </select>
                    </div>
                </div>
                <div class="toggle-grid" style="margin-top:10px;">
                    @include('partials.toggle', ['name' => 'zarinpal_sandbox', 'label' => 'حالت تست (Sandbox)', 'checked' => $zarinpal['sandbox']])
                </div>
                <p class="lead" style="margin-top:8px;">
                    وضعیت:
                    @if($zarinpal['configured'])
                        <span class="pill pill-ok">آماده اتصال</span>
                    @else
                        <span class="pill pill-off">Merchant ID وارد نشده</span>
                    @endif
                </p>

                <h3 style="margin-top:16px;">لینک موقت بانک‌ها (تا اتصال IPG)</h3>
                <div class="pay-settings-grid">
                    @foreach($paymentGateways as $gw)
                        <div class="pay-settings-card tone-{{ $gw['tone'] }}">
                            <label for="pay-{{ $gw['key'] }}">{{ $gw['label'] }}</label>
                            <small>{{ $gw['hint'] }}</small>
                            <input id="pay-{{ $gw['key'] }}"
                                   type="url"
                                   name="pay_link_{{ $gw['key'] }}"
                                   value="{{ old('pay_link_'.$gw['key'], $gw['url']) }}"
                                   placeholder="https://..."
                                   dir="ltr">
                        </div>
                    @endforeach
                </div>
                <div class="toggle-grid" style="margin-top:10px;">
                    @include('partials.toggle', ['name' => 'pay_links_show_reception', 'label' => 'نمایش لینک بانک در صفحه قبض', 'checked' => $paymentLinksShow['reception']])
                    @include('partials.toggle', ['name' => 'pay_links_show_invoice', 'label' => 'نمایش لینک بانک روی فاکتور چاپ', 'checked' => $paymentLinksShow['invoice']])
                    @include('partials.toggle', ['name' => 'portal_otp_debug', 'label' => 'نمایش کد OTP کارتابل مشتری (فقط تست)', 'checked' => $paymentLinksShow['otp_debug']])
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">ذخیره تنظیمات پرداخت</button>
                </div>
            </form>
        </div>

        <div class="ws-pane {{ $activeTab === 'sms' ? 'active' : '' }}" data-ws-pane="sms">
            <h2>پنل نیازپرداز</h2>
            <form method="POST" action="{{ route('settings.sms') }}">
                @csrf
                <input type="hidden" name="settings_tab" value="sms">
                <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                    <div><label>نام کاربری پنل</label><input type="text" name="niazpardaz_username" value="{{ $sms['username'] }}"></div>
                    <div><label>رمز پنل</label><input type="password" name="niazpardaz_password" value="{{ $sms['password'] }}"></div>
                    <div><label>API Key (اختیاری)</label><input type="text" name="niazpardaz_api_key" value="{{ $sms['api_key'] }}"></div>
                    <div><label>شماره فرستنده</label><input type="text" name="niazpardaz_from" value="{{ $sms['from'] }}" placeholder="1000..."></div>
                </div>
                <div class="actions"><button class="btn btn-primary" type="submit">ذخیره تنظیمات پیامک</button></div>
            </form>

            <div class="panel" style="margin-top:10px;">
                <h3>تست پنل پیامک</h3>
                <p class="lead">پس از ذخیره تنظیمات، یک پیامک تست به شماره دلخواه بفرستید تا اتصال پنل را بررسی کنید.</p>
                <form method="POST" action="{{ route('settings.sms.test') }}">
                    @csrf
                    <input type="hidden" name="settings_tab" value="sms">
                    <div class="accept-row accept-row-3">
                        <div>
                            <label>شماره موبایل گیرنده تست</label>
                            <input type="text" name="test_phone" value="{{ old('test_phone') }}" placeholder="09xxxxxxxxx" required>
                        </div>
                        <div style="display:flex;align-items:end;">
                            <button class="btn btn-secondary" type="submit">ارسال پیامک تست</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="ws-pane {{ $activeTab === 'backup' ? 'active' : '' }}" data-ws-pane="backup" id="backup">
            <h2>بکاپ خودکار دیتابیس</h2>
            <p class="lead">
                بکاپ بدون mysqldump (سازگار با هاست اشتراکی cPanel). می‌توانید بکاپ حسابداری را هفتگی به هاست FTP بفرستید.
            </p>

            @if(auth()->user()->canAccess('system.tools'))
            <div class="split-2" style="gap:10px;margin-bottom:12px;">
                <div class="panel" style="padding:10px;">
                    <h3 style="margin:0 0 6px;">ذخیره بکاپ روی کامپیوتر</h3>
                    <p class="muted" style="margin:0 0 8px;">ساخت بکاپ و دانلود فوری فایل روی سیستم شما.</p>
                    <form method="POST" action="{{ route('system-tools.run') }}">
                        @csrf
                        <input type="hidden" name="settings_tab" value="backup">
                        <input type="hidden" name="action" value="backup_save_pc">
                        <div class="accept-row accept-row-2" style="align-items:end;">
                            <div>
                                <label>نوع بکاپ</label>
                                <select name="scope">
                                    <option value="accounting" selected>حسابداری و مالی</option>
                                    <option value="full">کل دیتابیس</option>
                                </select>
                            </div>
                            <div class="actions" style="margin:0;">
                                <button class="btn btn-primary" type="submit">ساخت و ذخیره روی کامپیوتر</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="panel" style="padding:10px;">
                    <h3 style="margin:0 0 6px;">ریستور بکاپ از کامپیوتر</h3>
                    <p class="muted" style="margin:0 0 8px;">فایل `.sql` / `.sql.gz` را از کامپیوتر انتخاب کنید.</p>
                    <form method="POST" action="{{ route('system-tools.run') }}" enctype="multipart/form-data"
                          data-confirm="ریستور از فایل کامپیوتر؟ جداول داخل فایل جایگزین می‌شوند.">
                        @csrf
                        <input type="hidden" name="settings_tab" value="backup">
                        <input type="hidden" name="action" value="backup_restore_upload">
                        <div style="margin-bottom:8px;">
                            <input type="file" name="backup_file" accept=".sql,.gz,application/gzip" required>
                        </div>
                        @include('partials.toggle', [
                            'name' => 'confirm_restore',
                            'label' => 'تأیید ریستور از این فایل',
                            'checked' => false,
                        ])
                        <div class="actions" style="margin-top:8px;">
                            <button class="btn btn-danger" type="submit">ریستور از کامپیوتر</button>
                            <a class="btn btn-ghost" href="{{ route('system-tools.index') }}">ابزار کامل بکاپ</a>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('settings.backup') }}">
                @csrf
                <input type="hidden" name="settings_tab" value="backup">
                <div class="panel" style="margin-bottom:10px;">
                    <h3 style="margin-top:0;">زمان‌بندی</h3>
                    <div class="accept-row accept-row-4" style="align-items:end;">
                        <div>
                            @include('partials.toggle', [
                                'name' => 'enabled',
                                'label' => 'بکاپ خودکار فعال باشد',
                                'checked' => $backup['enabled'],
                            ])
                        </div>
                        <div>
                            <label>محدوده بکاپ</label>
                            <select name="scope">
                                @foreach($backupScopes as $key => $label)
                                    <option value="{{ $key }}" @selected($backup['scope'] === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>بازه تکرار</label>
                            <select name="interval">
                                @foreach($backupIntervals as $key => $label)
                                    <option value="{{ $key }}" @selected($backup['interval'] === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>روز هفته (برای هفتگی)</label>
                            <select name="weekday">
                                @foreach($backupWeekdays as $key => $label)
                                    <option value="{{ $key }}" @selected((int)$backup['weekday'] === (int)$key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>ساعت اجرا (۰–۲۳ تهران)</label>
                            <input type="number" name="hour" min="0" max="23" value="{{ $backup['hour'] }}">
                        </div>
                        <div>
                            <label>نگهداری محلی (تعداد فایل)</label>
                            <input type="number" name="keep_local" min="1" max="60" value="{{ $backup['keep_local'] }}">
                        </div>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:10px;">
                    <h3 style="margin-top:0;">هاست ریموت (FTP)</h3>
                    <p class="muted" style="margin-top:0;">آدرس هاستی که بکاپ باید هر هفته آنجا آپلود شود — مثلاً بکاپ‌سرور یا فضای FTP دیگر.</p>
                    <div class="accept-row accept-row-4" style="align-items:end;">
                        <div>
                            @include('partials.toggle', [
                                'name' => 'remote_enabled',
                                'label' => 'آپلود خودکار به هاست',
                                'checked' => $backup['remote_enabled'],
                            ])
                        </div>
                        <div>
                            <label>پروتکل</label>
                            <select name="remote_protocol">
                                @foreach($backupProtocols as $key => $label)
                                    <option value="{{ $key }}" @selected($backup['remote_protocol'] === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>آدرس هاست</label>
                            <input type="text" name="remote_host" value="{{ $backup['remote_host'] }}" placeholder="ftp.example.com" dir="ltr" style="text-align:left;">
                        </div>
                        <div>
                            <label>پورت</label>
                            <input type="number" name="remote_port" min="1" max="65535" value="{{ $backup['remote_port'] }}">
                        </div>
                        <div>
                            <label>نام کاربری</label>
                            <input type="text" name="remote_user" value="{{ $backup['remote_user'] }}" dir="ltr" style="text-align:left;" autocomplete="off">
                        </div>
                        <div>
                            <label>رمز عبور</label>
                            <input type="password" name="remote_password" value="" placeholder="{{ $backup['remote_password'] ? '•••••• (خالی = بدون تغییر)' : 'رمز FTP' }}" dir="ltr" style="text-align:left;" autocomplete="new-password">
                        </div>
                        <div style="grid-column:1/-1">
                            <label>مسیر پوشه روی هاست</label>
                            <input type="text" name="remote_path" value="{{ $backup['remote_path'] }}" placeholder="/backups" dir="ltr" style="text-align:left;">
                        </div>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:10px;">
                    <h3 style="margin-top:0;">کرون هاست (cPanel)</h3>
                    <p class="muted" style="margin:0 0 6px;">در cPanel → Cron Jobs این آدرس را هر ساعت صدا بزنید. سیستم خودش تشخیص می‌دهد آیا وقت بکاپ هفتگی/روزانه رسیده یا نه.</p>
                    <div class="accept-row accept-row-2">
                        <div style="grid-column:1/-1">
                            <label>دستور پیشنهادی Cron</label>
                            <input type="text" readonly dir="ltr" style="text-align:left;font-family:monospace;font-size:11px;"
                                   value="curl -fsS '{{ $backupCronUrl }}'">
                        </div>
                        <div>
                            <label>آخرین اجرا</label>
                            <div>{{ $backup['last_run_at'] ? jalali_like($backup['last_run_at']) : '—' }}</div>
                        </div>
                        <div>
                            <label>آخرین نتیجه</label>
                            <div>{{ $backup['last_message'] ?: '—' }}</div>
                        </div>
                    </div>
                    <p class="hint" style="margin-top:6px;">اجرای اجباری تست: <code dir="ltr">{{ $backupCronUrl }}&amp;force=1</code></p>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">ذخیره تنظیمات بکاپ</button>
                    <a class="btn btn-secondary" href="{{ route('system-tools.index') }}">رفتن به ابزار بکاپ / ریستور</a>
                </div>
            </form>
        </div>

        <div class="ws-pane {{ $activeTab === 'users' ? 'active' : '' }}" data-ws-pane="users">
            <div class="emp-cartable-hero">
                <div>
                    <h2 style="margin:0;">کارتابل کارمندان</h2>
                    <p class="lead" style="margin:4px 0 0;">ورود با موبایل و تأیید SMS — دسترسی طبق وظیفه</p>
                </div>
                <div class="actions" style="margin:0;">
                    <a class="btn btn-primary" href="{{ route('employees.create') }}">کارمند جدید</a>
                    <a class="btn btn-secondary" href="{{ route('employees.index') }}">کارتابل کامل</a>
                </div>
            </div>

            <div class="emp-stat-row" style="margin-top:10px;">
                <div class="emp-stat"><span>کل</span><strong>{{ $users->count() }}</strong></div>
                <div class="emp-stat tone-ok"><span>فعال</span><strong>{{ $users->where('is_active', true)->count() }}</strong></div>
                <div class="emp-stat tone-sms"><span>ورود SMS</span><strong>{{ $users->where('can_login_otp', true)->count() }}</strong></div>
                <div class="emp-stat tone-pass"><span>ورود رمز</span><strong>{{ $users->where('can_login_password', true)->count() }}</strong></div>
            </div>

            <div class="emp-card-grid" style="margin-top:10px;">
                @foreach($users->take(8) as $employee)
                    @php $meta = \App\Support\Permissions::roleMeta($employee->role); @endphp
                    <article class="emp-card tone-{{ $meta['tone'] }} {{ $employee->is_active ? '' : 'is-off' }}">
                        <header class="emp-card-head"><meta charset="utf-8">
                            <div class="emp-avatar">{{ $meta['mark'] }}</div>
                            <div>
                                <strong>{{ $employee->name }}</strong>
                                <div class="emp-duty">{{ $meta['label'] }}</div>
                            </div>
                        </header>
                        <div class="emp-card-body">
                            <div class="emp-phone" dir="ltr">{{ $employee->phone ?: '—' }}</div>
                            <div class="emp-login-chips">
                                @if($employee->can_login_otp)<span class="chip chip-sms">موبایل / SMS</span>@endif
                                @if($employee->can_login_password)<span class="chip chip-pass">رمز</span>@endif
                            </div>
                        </div>
                        <footer class="emp-card-foot">
                            <a class="btn btn-secondary" href="{{ route('employees.edit', $employee) }}">دسترسی / وظیفه</a>
                        </footer>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
