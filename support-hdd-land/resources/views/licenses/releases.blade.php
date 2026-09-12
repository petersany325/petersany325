@extends('layouts.app')
@section('title', 'انتشار آپدیت | '.shop_name())
@section('page_title', 'انتشار آپدیت برای مشتریان')
@section('window_title', 'ZIP + مانیفست کانال پایدار')

@section('content')
<div class="sys-tools">
    <section class="sys-hero panel">
        <div class="sys-hero-copy">
            <p class="sys-eyebrow">سرور لایسنس</p>
            <h2>انتشار آپدیت</h2>
            <p class="lead">
                فایل ZIP بسته نرم‌افزار (ریشه پروژه با artisan و app/) را آپلود کنید.
                پنل مشتریان از مسیر «ابزارهای سیستم ← آپدیت نرم‌افزار» به‌صورت زنده آن را می‌بیند و نصب می‌کند.
            </p>
        </div>
        <div class="sys-hero-status">
            <div class="sys-status-title">مانیفست فعلی</div>
            <ul>
                <li>کانال: {{ $manifest['channel'] ?? 'stable' }}</li>
                <li>آخرین: <strong>{{ $manifest['latest'] ?? '—' }}</strong></li>
                <li>نسخه این سرور: {{ $currentApp }}</li>
                <li>تعداد انتشار: {{ count($manifest['releases'] ?? []) }}</li>
            </ul>
        </div>
    </section>

    @if(!$isSeller)
        <div class="panel" style="border-color:#fecaca;background:#fef2f2;">
            این نصب مشتری است (LICENSE_KEY دارد). انتشار آپدیت فقط روی سرور فروشنده انجام شود.
        </div>
    @else
        <section class="panel" style="margin-top:12px;">
            <h3 style="margin-top:0;">آپلود نسخه جدید</h3>
            <form method="POST" action="{{ route('licenses.releases.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid" style="display:grid;gap:12px;max-width:560px;">
                    <label>
                        نسخه (مثلاً 1.1.0)
                        <input type="text" name="version" value="{{ old('version') }}" required pattern="\d+\.\d+(\.\d+)?" class="input" style="width:100%;">
                    </label>
                    <label>
                        فهرست تغییرات (هر خط یک مورد)
                        <textarea name="changelog" rows="5" class="input" style="width:100%;">{{ old('changelog') }}</textarea>
                    </label>
                    <label>
                        فایل ZIP
                        <input type="file" name="zip" accept=".zip,application/zip" required>
                    </label>
                    <label style="display:flex;gap:8px;align-items:center;">
                        <input type="checkbox" name="set_latest" value="1" checked>
                        به‌عنوان آخرین نسخه کانال پایدار تنظیم شود
                    </label>
                    <button type="submit" class="btn btn-primary">انتشار</button>
                </div>
            </form>
        </section>
    @endif

    <section class="panel" style="margin-top:16px;">
        <h3 style="margin-top:0;">تاریخچه انتشار</h3>
        @if(empty($manifest['releases']))
            <p class="muted">هنوز آپدیتی منتشر نشده. نمونه مانیفست: <code>storage/app/releases/manifest.example.json</code></p>
        @else
            <table class="table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:right;padding:8px;border-bottom:1px solid #e2e8f0;">نسخه</th>
                        <th style="text-align:right;padding:8px;border-bottom:1px solid #e2e8f0;">تاریخ</th>
                        <th style="text-align:right;padding:8px;border-bottom:1px solid #e2e8f0;">فایل</th>
                        <th style="text-align:right;padding:8px;border-bottom:1px solid #e2e8f0;">تغییرات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($manifest['releases'] as $row)
                        <tr>
                            <td style="padding:8px;border-bottom:1px solid #f1f5f9;"><strong>{{ $row['version'] ?? '—' }}</strong>
                                @if(($row['version'] ?? '') === ($manifest['latest'] ?? ''))
                                    <span class="muted">(آخرین)</span>
                                @endif
                            </td>
                            <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ !empty($row['released_at']) ? jalali_date($row['released_at']) : '—' }}</td>
                            <td style="padding:8px;border-bottom:1px solid #f1f5f9;font-size:12px;direction:ltr;">{{ $row['file'] ?? '—' }}</td>
                            <td style="padding:8px;border-bottom:1px solid #f1f5f9;font-size:13px;">
                                @foreach(($row['changelog'] ?? []) as $c)
                                    <div>• {{ $c }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
