@extends('layouts.app')
@section('title', 'ابزارهای سیستم | سرزمین هارد')
@section('page_title', 'ابزارهای سیستم')
@section('window_title', 'نگهداری، کش، تعمیر و بکاپ دیتابیس')

@section('content')
<div class="sys-tools">
    <section class="sys-hero panel">
        <div class="sys-hero-copy">
            <p class="sys-eyebrow">نگهداری سیستم</p>
            <h2>ابزارهای سیستم</h2>
            <p class="lead">پاک‌سازی کش، تعمیر/بازسازی دیتابیس، بکاپ و ریستور — بدون حذف داده در بازسازی.</p>
        </div>
        <div class="sys-hero-status">
            <div class="sys-status-title">وضعیت فعلی</div>
            <ul>
                @foreach($snapshot as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="sys-grid">
        <article class="sys-card tone-cache">
            <div class="sys-card-mark">کش</div>
            <h3>پاک‌سازی کش سایت</h3>
            <p>کش برنامه، تنظیمات، مسیرها و ویوها را خالی می‌کند.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="clear_cache">
                <button class="btn btn-primary" type="submit">پاک‌سازی کش</button>
            </form>
        </article>

        <article class="sys-card tone-cache">
            <div class="sys-card-mark">بهینه</div>
            <h3>بازسازی کش (بهینه‌سازی)</h3>
            <p>ابتدا کش‌ها را پاک می‌کند، سپس config/route/view را دوباره می‌سازد.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="rebuild_cache">
                <button class="btn btn-secondary" type="submit">بازسازی کش</button>
            </form>
        </article>

        <article class="sys-card tone-db">
            <div class="sys-card-mark">سلامت</div>
            <h3>بررسی سلامت دیتابیس</h3>
            <p>اتصال، تعداد جداول، CHECK TABLE و مایگریشن‌های معوق.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="db_health">
                <button class="btn btn-secondary" type="submit">بررسی سلامت</button>
            </form>
        </article>

        <article class="sys-card tone-db">
            <div class="sys-card-mark">تعمیر</div>
            <h3>تعمیر دیتابیس</h3>
            <p>MyISAM: REPAIR — InnoDB: ANALYZE (ایمن). داده حذف نمی‌شود.</p>
            <form method="POST" action="{{ route('system-tools.run') }}" data-confirm="تعمیر/تحلیل جداول اجرا شود؟">
                @csrf
                <input type="hidden" name="action" value="db_repair">
                <button class="btn btn-secondary" type="submit">تعمیر جداول</button>
            </form>
        </article>

        <article class="sys-card tone-db">
            <div class="sys-card-mark">بهینه</div>
            <h3>بهینه‌سازی جداول</h3>
            <p>OPTIMIZE TABLE برای کاهش فضای هدررفته.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="db_optimize">
                <button class="btn btn-ghost" type="submit">بهینه‌سازی</button>
            </form>
        </article>

        <article class="sys-card tone-danger">
            <div class="sys-card-mark">بازسازی</div>
            <h3>بازسازی دیتابیس آسیب‌دیده</h3>
            <p>بررسی → تعمیر/ANALYZE → بهینه‌سازی → مایگریشن معوق → پاک‌سازی کش. بدون حذف داده.</p>
            <form method="POST" action="{{ route('system-tools.run') }}" data-confirm="بازسازی دیتابیس شروع شود؟ داده حذف نمی‌شود.">
                @csrf
                <input type="hidden" name="action" value="db_rebuild">
                <button class="btn btn-primary" type="submit">شروع بازسازی</button>
            </form>
        </article>

        <article class="sys-card tone-migrate">
            <div class="sys-card-mark">مایگر</div>
            <h3>اجرای مایگریشن‌های معوق</h3>
            <p>فقط مایگریشن‌های اجرا‌نشده را اعمال می‌کند.</p>
            <form method="POST" action="{{ route('system-tools.run') }}" data-confirm="مایگریشن‌های معوق اجرا شوند؟">
                @csrf
                <input type="hidden" name="action" value="migrate">
                <button class="btn btn-secondary" type="submit">اجرای مایگریشن</button>
            </form>
        </article>
    </section>

    <section class="panel backup-pc-panel" style="margin-top:4px;">
        <h2 style="margin:0 0 6px;">بکاپ و ریستور دیتابیس</h2>
        <p class="lead" style="margin:0 0 10px;">
            ذخیره بکاپ روی کامپیوتر شما، ریستور از فایل کامپیوتر، و بکاپ روی سرور/هاست FTP.
            زمان‌بندی خودکار: <a href="{{ route('settings.index') }}#backup">تنظیمات → بکاپ</a>.
        </p>

        @if(!empty($downloadFile))
            <div class="alert alert-success" style="margin-bottom:10px;">
                بکاپ آماده است —
                <a class="btn btn-primary" id="backup-auto-dl-tools" href="{{ route('system-tools.backups.download', $downloadFile) }}">دانلود و ذخیره روی کامپیوتر</a>
                <span class="muted" dir="ltr" style="margin-right:8px;">{{ $downloadFile }}</span>
            </div>
        @endif

        <div class="split-2 backup-pc-grid" style="gap:12px;margin-bottom:12px;">
            <article class="sys-card tone-db backup-pc-card">
                <div class="sys-card-mark">PC</div>
                <h3>ذخیره بکاپ روی کامپیوتر</h3>
                <p>بکاپ ساخته می‌شود و بلافاصله فایل `.sql.gz` روی کامپیوتر شما دانلود/ذخیره می‌شود.</p>
                <form method="POST" action="{{ route('system-tools.run') }}" class="backup-pc-form">
                    @csrf
                    <input type="hidden" name="action" value="backup_save_pc">
                    <div style="margin-bottom:8px;">
                        <label>نوع بکاپ</label>
                        <select name="scope">
                            <option value="full" selected>کامل سیستم (پیشنهادی برای ریستور/هاست جدید)</option>
                            <option value="accounting">فقط حسابداری و مالی</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">ساخت و ذخیره روی کامپیوتر</button>
                </form>
            </article>

            <article class="sys-card tone-danger backup-pc-card">
                <div class="sys-card-mark">ریستور</div>
                <h3>ریستور بکاپ از کامپیوتر</h3>
                <p>فایل بکاپ `.sql` یا `.sql.gz` را از کامپیوتر انتخاب کنید و به دیتابیس برگردانید. قبلش بکاپ تازه بگیرید.</p>
                <form method="POST" action="{{ route('system-tools.run') }}" enctype="multipart/form-data" class="backup-pc-form"
                      data-confirm="ریستور از فایل کامپیوتر انجام شود؟ جداول داخل فایل جایگزین می‌شوند.">
                    @csrf
                    <input type="hidden" name="action" value="backup_restore_upload">
                    <div style="margin-bottom:8px;">
                        <label>انتخاب فایل از کامپیوتر</label>
                        <input type="file" name="backup_file" accept=".sql,.gz,application/gzip,application/sql" required>
                    </div>
                    @include('partials.toggle', [
                        'name' => 'confirm_restore',
                        'label' => 'تأیید می‌کنم ریستور از این فایل انجام شود',
                        'checked' => false,
                    ])
                    <div class="actions" style="margin-top:8px;">
                        <button class="btn btn-danger" type="submit">ریستور از کامپیوتر</button>
                    </div>
                </form>
            </article>
        </div>

        <div class="sys-grid" style="margin-bottom:12px;">
            <article class="sys-card tone-db">
                <div class="sys-card-mark">بکاپ</div>
                <h3>بکاپ کامل (روی سرور)</h3>
                <p>کل جداول روی سرور ذخیره می‌شود؛ بعد می‌توانید دانلود کنید.</p>
                <form method="POST" action="{{ route('system-tools.run') }}">
                    @csrf
                    <input type="hidden" name="action" value="backup_full">
                    <button class="btn btn-secondary" type="submit">ساخت روی سرور</button>
                </form>
            </article>
            <article class="sys-card tone-db">
                <div class="sys-card-mark">مالی</div>
                <h3>بکاپ حسابداری (روی سرور)</h3>
                <p>حساب‌ها، اسناد، پرداخت‌ها، قبض‌ها و انبار — روی سرور</p>
                <form method="POST" action="{{ route('system-tools.run') }}">
                    @csrf
                    <input type="hidden" name="action" value="backup_accounting">
                    <button class="btn btn-secondary" type="submit">ساخت روی سرور</button>
                </form>
            </article>
            <article class="sys-card tone-migrate">
                <div class="sys-card-mark">اتو</div>
                <h3>اجرای بکاپ خودکار الآن</h3>
                <p>طبق تنظیمات (محدوده + FTP + کلودهای فعال)</p>
                <form method="POST" action="{{ route('system-tools.run') }}">
                    @csrf
                    <input type="hidden" name="action" value="backup_run_now">
                    <button class="btn btn-ghost" type="submit">اجرا الآن</button>
                </form>
            </article>
            <article class="sys-card tone-db">
                <div class="sys-card-mark">کلود</div>
                <h3>آپلود به کلودها</h3>
                <p>ساخت بکاپ کامل و ارسال به Google / OneDrive / MEGA فعال‌شده</p>
                <form method="POST" action="{{ route('system-tools.run') }}">
                    @csrf
                    <input type="hidden" name="action" value="backup_upload_clouds">
                    <button class="btn btn-secondary" type="submit">ساخت و آپلود به کلود</button>
                </form>
            </article>
        </div>

        <div class="panel" style="margin-bottom:12px;padding:10px;">
            <h3 style="margin:0 0 6px;">وضعیت بکاپ خودکار</h3>
            <ul style="margin:0;padding-right:16px;font-size:12px;">
                <li>فعال: {{ !empty($backupCfg['enabled']) ? 'بله' : 'خیر' }}</li>
                <li>محدوده: {{ \App\Support\BackupSettings::SCOPES[$backupCfg['scope']] ?? $backupCfg['scope'] }}</li>
                <li>بازه: {{ \App\Support\BackupSettings::INTERVALS[$backupCfg['interval']] ?? $backupCfg['interval'] }}</li>
                <li>آخرین: {{ $backupCfg['last_run_at'] ? jalali_like($backupCfg['last_run_at']) : '—' }}</li>
                <li>نتیجه: {{ $backupCfg['last_message'] ?: '—' }}</li>
            </ul>
            <div class="actions" style="margin-top:8px;">
                <a class="btn btn-ghost" href="{{ route('settings.index') }}#backup">تنظیم هاست و زمان‌بندی</a>
                <form method="POST" action="{{ route('system-tools.run') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="action" value="backup_test_remote">
                    <button class="btn btn-secondary" type="submit">تست اتصال FTP</button>
                </form>
            </div>
        </div>

        <h3 style="margin:0 0 6px;">فایل‌های بکاپ روی سرور — دانلود / ریستور</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>فایل</th>
                    <th>نوع</th>
                    <th>حجم</th>
                    <th>زمان</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($backups as $b)
                    <tr>
                        <td dir="ltr" style="text-align:left;">{{ $b['name'] }}</td>
                        <td>{{ $b['scope'] === 'accounting' ? 'حسابداری' : 'کامل' }}</td>
                        <td>{{ number_format($b['size'] / 1024, 1) }} KB</td>
                        <td>{{ jalali_like(\Illuminate\Support\Carbon::createFromTimestamp($b['mtime'])) }}</td>
                        <td>
                            <div class="actions" style="flex-wrap:wrap;">
                                <a class="btn btn-primary" href="{{ route('system-tools.backups.download', $b['name']) }}">ذخیره روی کامپیوتر</a>
                                <form method="POST" action="{{ route('system-tools.run') }}">
                                    @csrf
                                    <input type="hidden" name="action" value="backup_upload_remote">
                                    <input type="hidden" name="file" value="{{ $b['name'] }}">
                                    <button class="btn btn-secondary" type="submit">ارسال به هاست</button>
                                </form>
                                <form method="POST" action="{{ route('system-tools.run') }}" data-confirm="ریستور این بکاپ؟ جداول فایل جایگزین می‌شوند.">
                                    @csrf
                                    <input type="hidden" name="action" value="backup_restore_local">
                                    <input type="hidden" name="file" value="{{ $b['name'] }}">
                                    <input type="hidden" name="confirm_restore" value="1">
                                    <button class="btn btn-ghost" type="submit">ریستور</button>
                                </form>
                                <form method="POST" action="{{ route('system-tools.run') }}" data-confirm="حذف این فایل بکاپ؟">
                                    @csrf
                                    <input type="hidden" name="action" value="backup_delete">
                                    <input type="hidden" name="file" value="{{ $b['name'] }}">
                                    <button class="btn btn-danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">هنوز بکاپی روی سرور نیست. از «ذخیره بکاپ روی کامپیوتر» استفاده کنید.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if(!empty($lastResult))
        <section class="panel sys-result {{ !empty($lastResult['ok']) ? 'is-ok' : 'is-bad' }}">
            <h3>نتیجه آخرین عملیات</h3>
            <p class="lead" style="margin:0 0 8px;">{{ $lastResult['message'] ?? '' }}</p>
            @if(!empty($lastResult['details']))
                <div class="sys-log">
                    @foreach($lastResult['details'] as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <p class="muted sys-note">بازسازی داده را پاک نمی‌کند. ریستور فقط جداول موجود در فایل بکاپ را جایگزین می‌کند — قبل از ریستور حتماً بکاپ تازه بگیرید.</p>
</div>
@endsection
