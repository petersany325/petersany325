@extends('layouts.app')
@section('title', 'آماده‌سازی و انتشار آپدیت | '.shop_name())
@section('page_title', 'آماده‌سازی و انتشار آپدیت')
@section('window_title', 'تست انتخابی تغییرات → انتشار برای مشتریان')

@section('content')
    @if(!$isSeller)
        <div class="panel" style="border-color:#fecaca;background:#fef2f2;margin-top:12px;">
            این نصب مشتری است. آماده‌سازی و انتشار آپدیت فقط روی سرور فروشنده انجام می‌شود.
        </div>
    @else
    @php
        $tab = request('tab', 'board');
        if (! in_array($tab, ['board', 'publish', 'history'], true)) {
            $tab = 'board';
        }
    @endphp
<div class="sys-tools release-board">
    <section class="sys-hero panel">
        <div class="sys-hero-copy">
            <p class="sys-eyebrow">سرور لایسنس / ادمین</p>
            <h2>تابلو تغییرات و انتشار مشتری</h2>
            <p class="lead">
                بعد از هر تغییر، اینجا ثبت کنید → تست انتخابی بزنید → موارد تأییدشده را به آپدیت مشتری اضافه کنید.
            </p>
        </div>
        <div class="sys-hero-status">
            <div class="sys-status-title">وضعیت کانال</div>
            <ul>
                <li>آخرین منتشرشده: <strong>{{ $manifest['latest'] ?? '—' }}</strong></li>
                <li>پیشنهاد نسخه بعد: <strong dir="ltr">{{ $suggestedVersion }}</strong></li>
                <li>انتخاب‌شده برای انتشار: <strong>{{ $selectedCount }}</strong></li>
                <li>انتخابیِ تست‌نشده: <strong>{{ $untestedSelected }}</strong></li>
            </ul>
        </div>
    </section>

    <div class="ws-tabs" style="margin-top:12px;">
        <a class="btn {{ $tab === 'board' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('licenses.releases', ['tab' => 'board']) }}">۱) تابلو تغییرات</a>
        <a class="btn {{ $tab === 'publish' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('licenses.releases', ['tab' => 'publish']) }}">۲) انتشار برای مشتری</a>
        <a class="btn {{ $tab === 'history' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('licenses.releases', ['tab' => 'history']) }}">۳) تاریخچه</a>
    </div>

    @if($tab === 'board')
        <section class="panel" style="margin-top:12px;">
            <h3 style="margin-top:0;">ثبت تغییر جدید</h3>
            <form method="POST" action="{{ route('licenses.releases.changes.store') }}">
                @csrf
                <div class="accept-row accept-row-2">
                    <div>
                        <label>عنوان تغییر</label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="مثال: منوی تخصص و حقوق">
                    </div>
                    <div>
                        <label>مسیر تست (اختیاری)</label>
                        <input type="text" name="test_url" value="{{ old('test_url') }}" placeholder="/employees/pay" dir="ltr" style="text-align:left;">
                    </div>
                </div>
                <div style="margin-top:8px;">
                    <label>خلاصه برای مشتری / ادمین</label>
                    <input type="text" name="summary" value="{{ old('summary') }}" placeholder="یک جمله کوتاه">
                </div>
                <div style="margin-top:8px;">
                    <label>فایل‌های مرتبط (هر خط یک مسیر نسبی)</label>
                    <textarea name="files" rows="4" placeholder="app/Http/Controllers/...&#10;resources/views/...">{{ old('files') }}</textarea>
                </div>
                <div style="margin-top:8px;">
                    <label>یادداشت تست</label>
                    <input type="text" name="test_notes" value="{{ old('test_notes') }}" placeholder="چه چیزی را در UI چک کنیم؟">
                </div>
                <div class="actions" style="margin-top:10px;">
                    <button class="btn btn-primary" type="submit">افزودن به تابلو</button>
                </div>
            </form>
        </section>

        <section class="panel" style="margin-top:12px;">
            <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
                <h3 style="margin:0;">لیست تغییرات آماده تست / انتخاب</h3>
                <a class="btn btn-secondary" href="{{ route('licenses.releases', ['tab' => 'publish']) }}">رفتن به انتشار مشتری ({{ $selectedCount }} انتخاب)</a>
            </div>

            @forelse($boardItems as $item)
                @php
                    $id = $item['id'] ?? '';
                    $tested = ! empty($item['tested']);
                    $selected = ! empty($item['selected']);
                @endphp
                <article class="change-card {{ $tested ? 'is-tested' : '' }} {{ $selected ? 'is-selected' : '' }}">
                    <form method="POST" action="{{ route('licenses.releases.changes.select', $id) }}" class="change-select">
                        @csrf
                        <input type="hidden" name="selected" value="{{ $selected ? '0' : '1' }}">
                        <button class="btn {{ $selected ? 'btn-primary' : 'btn-ghost' }}" type="submit">
                            {{ $selected ? '✓ در انتشار مشتری' : 'افزودن به انتشار مشتری' }}
                        </button>
                    </form>
                    <div class="change-main">
                        <strong>{{ $item['title'] ?? '—' }}</strong>
                        @if(!empty($item['summary']))
                            <div class="muted">{{ $item['summary'] }}</div>
                        @endif
                        @if(!empty($item['test_notes']))
                            <div class="change-test-notes">تست: {{ $item['test_notes'] }}</div>
                        @endif
                        @if(!empty($item['files']))
                            <details style="margin-top:6px;">
                                <summary class="muted">{{ count($item['files']) }} فایل</summary>
                                <ul class="change-files">
                                    @foreach($item['files'] as $f)
                                        <li dir="ltr">{{ $f }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                    <div class="change-actions">
                        @if(!empty($item['test_url']))
                            <a class="btn btn-ghost" href="{{ url($item['test_url']) }}" target="_blank" rel="noopener">باز کردن صفحه تست</a>
                        @endif
                        <form method="POST" action="{{ route('licenses.releases.changes.test', $id) }}">
                            @csrf
                            <input type="hidden" name="tested" value="{{ $tested ? '0' : '1' }}">
                            <button class="btn {{ $tested ? 'btn-secondary' : 'btn-primary' }}" type="submit">
                                {{ $tested ? 'تست‌شده ✓' : 'علامت تست شد' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('licenses.releases.changes.destroy', $id) }}" data-confirm="این تغییر از تابلو حذف شود؟">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">حذف</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="muted">تابلو خالی است. اولین تغییر را ثبت کنید.</p>
            @endforelse
        </section>
    @endif

    @if($tab === 'publish')
        <section class="panel" style="margin-top:12px;">
            <h3 style="margin-top:0;">انتشار آپدیت برای مشتریان</h3>
            <p class="lead">تغییرات انتخاب‌شده از تابلو به‌صورت ZIP انتخابی ساخته می‌شوند و در مانیفست لایسنس قرار می‌گیرند.</p>

            @if($selectedCount === 0)
                <div class="alert alert-error">هنوز تغییری انتخاب نشده. از زبانه تابلو، آیتم‌ها را تیک بزنید.</div>
            @else
                <div class="panel" style="background:#f8fafc;margin-bottom:12px;">
                    <strong>{{ $selectedCount }} تغییر انتخاب شده</strong>
                    @if($untestedSelected > 0)
                        <div class="muted">⚠️ {{ $untestedSelected }} مورد هنوز تست نشده.</div>
                    @endif
                    <ul style="margin:8px 0 0;padding-right:18px;">
                        @foreach(preg_split('/\r\n|\r|\n/', $selectedChangelog) ?: [] as $line)
                            @if(trim($line) !== '')
                                <li>{{ $line }}</li>
                            @endif
                        @endforeach
                    </ul>
                    @if(!empty($selectedFiles))
                        <details style="margin-top:8px;">
                            <summary class="muted">{{ count($selectedFiles) }} فایل در بسته</summary>
                            <ul class="change-files">
                                @foreach($selectedFiles as $f)
                                    <li dir="ltr">{{ $f }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('licenses.releases.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="source" value="board">
                <div class="form-grid" style="display:grid;gap:12px;max-width:640px;">
                    <label>
                        نسخه جدید
                        <input type="text" name="version" value="{{ old('version', $suggestedVersion) }}" required pattern="\d+\.\d+(\.\d+)?" class="input" style="width:100%;" dir="ltr">
                    </label>
                    <label>
                        فهرست تغییرات مشتری (هر خط یک مورد)
                        <textarea name="changelog" rows="6" class="input" style="width:100%;">{{ old('changelog', $selectedChangelog) }}</textarea>
                    </label>
                    <label style="display:flex;gap:8px;align-items:center;">
                        <input type="checkbox" name="require_tested" value="1" @checked(old('require_tested', '1') === '1')>
                        فقط اگر همه انتخاب‌ها تست شده باشند منتشر شود
                    </label>
                    <label style="display:flex;gap:8px;align-items:center;">
                        <input type="checkbox" name="set_latest" value="1" checked>
                        به‌عنوان آخرین نسخه کانال پایدار تنظیم شود
                    </label>
                    <button type="submit" class="btn btn-primary" @if($selectedCount === 0) disabled @endif>ساخت ZIP انتخابی و انتشار برای مشتری</button>
                </div>
            </form>
        </section>

        <section class="panel" style="margin-top:12px;">
            <h3 style="margin-top:0;">انتشار دستی با ZIP کامل (اختیاری)</h3>
            <form method="POST" action="{{ route('licenses.releases.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="source" value="upload">
                <div class="form-grid" style="display:grid;gap:12px;max-width:560px;">
                    <label>
                        نسخه
                        <input type="text" name="version" value="{{ old('version') }}" required pattern="\d+\.\d+(\.\d+)?" class="input" style="width:100%;" dir="ltr">
                    </label>
                    <label>
                        فهرست تغییرات
                        <textarea name="changelog" rows="4" class="input" style="width:100%;">{{ old('changelog') }}</textarea>
                    </label>
                    <label>
                        فایل ZIP
                        <input type="file" name="zip" accept=".zip,application/zip" required>
                    </label>
                    <label style="display:flex;gap:8px;align-items:center;">
                        <input type="checkbox" name="set_latest" value="1" checked>
                        آخرین نسخه کانال پایدار
                    </label>
                    <button type="submit" class="btn btn-secondary">انتشار ZIP دستی</button>
                </div>
            </form>
        </section>
    @endif

    @if($tab === 'history')
        <section class="panel" style="margin-top:12px;">
            <h3 style="margin-top:0;">تاریخچه انتشار مشتری</h3>
            @if(empty($manifest['releases']))
                <p class="muted">هنوز آپدیتی منتشر نشده.</p>
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

        @if(!empty($boardPublished))
            <section class="panel" style="margin-top:12px;">
                <h3 style="margin-top:0;">بایگانی تابلو</h3>
                @foreach($boardPublished as $pack)
                    <div style="margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #eef2f7;">
                        <strong dir="ltr">{{ $pack['version'] ?? '—' }}</strong>
                        <span class="muted">{{ !empty($pack['published_at']) ? jalali_date($pack['published_at']) : '' }}</span>
                        <ul style="margin:6px 0 0;padding-right:18px;">
                            @foreach(($pack['changelog'] ?? []) as $c)
                                <li>{{ $c }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </section>
        @endif
    @endif
</div>
    @endif
@endsection
