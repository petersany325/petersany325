@extends('layouts.app')
@section('title', 'تعریف وضعیت‌های دستگاه تعمیری | سرزمین هارد')
@section('page_title', 'تعریف تغییر وضعیت / پیامک')
@section('window_title', 'تعریف وضعیت‌های دستگاه تعمیری')

@section('content')
<div class="sms-desk" id="sms-desk" data-workspace-tabs>
    <div class="sms-desk-toolbar">
        <div>
            <h2>تعریف وضعیت‌های دستگاه تعمیری</h2>
            <p class="lead">وضعیت‌ها را تعریف کنید و برای هر کدام بگویید پیامک «ارسال شود»، «ارسال نشود» یا «پرسیده شود». در حالت پرسیده شود، موقع ثبت از کارمند سؤال می‌شود.</p>
        </div>
        <form method="POST" action="{{ route('sms-statuses.master') }}" class="sms-master-form">
            @csrf
            @include('partials.toggle', [
                'name' => 'sms_master_enabled',
                'label' => 'اجازه ارسال پیامک در سیستم',
                'checked' => $masterEnabled,
                'on' => 'برود',
                'off' => 'نرود',
            ])
            <button class="btn btn-primary" type="submit">اعمال</button>
        </form>
    </div>

    <div class="ws-tabs">
        <button type="button" class="active" data-ws-tab="define">تعریف وضعیت‌ها</button>
        <button type="button" data-ws-tab="gateway">تنظیمات ارسال SMS</button>
        <button type="button" data-ws-tab="logs">گزارش ارسال</button>
    </div>

    <div class="ws-panes">
        <div class="ws-pane active" data-ws-pane="define">
            <div class="panel sms-table-panel">
                <div class="table-wrap">
                    <table class="sms-status-table" id="sms-status-table">
                        <thead>
                        <tr>
                            <th>نام وضعیت</th>
                            <th>خلاصه</th>
                            <th>اولویت</th>
                            <th>نوع</th>
                            <th>نتیجه</th>
                            <th>توضیحات</th>
                            <th>پیامک</th>
                            <th>همکار</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rules as $rule)
                            <tr class="sms-row {{ $rule->is_hidden ? 'is-hidden-row' : '' }}"
                                data-rule-id="{{ $rule->id }}"
                                data-title="{{ $rule->title }}"
                                data-summary="{{ $rule->summary }}"
                                data-status-key="{{ $rule->status_key }}"
                                data-sort="{{ $rule->sort_order }}"
                                data-stage="{{ $rule->stage_type }}"
                                data-result="{{ $rule->result_type }}"
                                data-color="{{ $rule->color }}"
                                data-description="{{ $rule->description }}"
                                data-message="{{ e($rule->message_template) }}"
                                data-auto-send="{{ $rule->shouldAutoSend() ? '1' : '0' }}"
                                data-send-mode="{{ $rule->sendMode() }}"
                                data-coworker-message="{{ e($rule->coworker_message_template) }}"
                                data-send-coworker="{{ $rule->send_coworker ? '1' : '0' }}"
                                data-active="{{ $rule->is_active ? '1' : '0' }}"
                                data-hidden="{{ $rule->is_hidden ? '1' : '0' }}"
                                data-on-create="{{ $rule->on_create ? '1' : '0' }}"
                                data-on-price="{{ $rule->on_price ? '1' : '0' }}"
                                data-update-url="{{ route('sms-statuses.update', $rule) }}"
                                data-delete-url="{{ route('sms-statuses.destroy', $rule) }}"
                                data-hide-url="{{ route('sms-statuses.hide', $rule) }}">
                                <td><strong>{{ $rule->title }}</strong>
                                    @if($rule->on_create) <span class="pill">ثبت قبض</span>@endif
                                    @if($rule->on_price) <span class="pill">مبلغ</span>@endif
                                </td>
                                <td>{{ $rule->summary ?: '—' }}</td>
                                <td>{{ $rule->sort_order }}</td>
                                <td>{{ $rule->stageLabel() }}</td>
                                <td>{{ $rule->resultLabel() }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($rule->description, 40) ?: '—' }}</td>
                                <td>
                                    @if($rule->sendMode() === 'ask')
                                        <span class="pill">پرسیده شود</span>
                                    @elseif($rule->shouldAutoSend())
                                        <span class="pill pill-ok">ارسال شود</span>
                                    @else
                                        <span class="pill pill-off">ارسال نشود</span>
                                    @endif
                                </td>
                                <td>{!! $rule->send_coworker ? '<span class="pill pill-ok">بله</span>' : '<span class="pill pill-off">خیر</span>' !!}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form method="POST" action="{{ route('sms-statuses.store') }}" class="panel sms-editor" id="sms-editor-form">
                @csrf
                <input type="hidden" name="_method" id="editor-method" value="POST">
                <input type="hidden" name="status_key" id="f-status-key" value="">

                <div class="sms-editor-title">
                    <h3 id="editor-heading">افزودن وضعیت جدید</h3>
                    <span class="muted" id="editor-mode-hint">ردیف جدول را انتخاب کنید تا ویرایش شود</span>
                </div>

                <div class="accept-row accept-row-4">
                    <div>
                        <label>نام وضعیت</label>
                        <input type="text" name="title" id="f-title" required placeholder="مثلاً منتظر قطعه">
                    </div>
                    <div>
                        <label>خلاصه</label>
                        <input type="text" name="summary" id="f-summary" placeholder="قطعه">
                    </div>
                    <div>
                        <label>اولویت</label>
                        <input type="number" name="sort_order" id="f-sort" min="0" value="10">
                    </div>
                    <div>
                        <label>رنگ نشان</label>
                        <select name="color" id="f-color">
                            @foreach($colors as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>نوع</label>
                        <select name="stage_type" id="f-stage" required>
                            @foreach($stageTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>نتیجه</label>
                        <select name="result_type" id="f-result" required>
                            @foreach($resultTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="grid-column:1/-1">
                        <label>توضیحات</label>
                        <input type="text" name="description" id="f-description" placeholder="دستگاه تازه پذیرش شده">
                    </div>
                </div>

                <div class="sms-msg-block">
                    <div class="sms-msg-head">
                        <strong>پیامک مشتری</strong>
                        <div class="sms-msg-tools">
                            <label class="inline-label">ارسال
                                <select name="send_mode" id="f-send-mode">
                                    <option value="always">ارسال شود</option>
                                    <option value="never">ارسال نشود</option>
                                    <option value="ask">پرسیده شود</option>
                                </select>
                            </label>
                            <input type="hidden" name="auto_send" id="f-auto-send" value="1">
                            <label class="inline-label">کلید واژه
                                <select id="kw-customer">
                                    @foreach($placeholders as $token => $label)
                                        <option value="{{ $token }}">{{ $label }} — {{ $token }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="button" class="btn btn-secondary" data-insert-into="f-message" data-kw="kw-customer">درج</button>
                        </div>
                    </div>
                    <textarea name="message_template" id="f-message" rows="5" placeholder="متن پیامک مشتری...">سلام {customer_name}
دستگاه شما ({device}) — سریال {serial}
خرابی: {fault}
وضعیت: {status}
قبض: {ticket_no}</textarea>
                    <div class="sms-counters">
                        <span>طول پیام: <strong id="len-customer">0</strong></span>
                        <span>تعداد پیامک: <strong id="parts-customer">0</strong></span>
                    </div>
                </div>

                <div class="sms-msg-block">
                    <div class="sms-msg-head">
                        <strong>پیامک به همکار / تعمیرکار</strong>
                        <div class="sms-msg-tools">
                            <label class="inline-label">ارسال
                                <select id="f-coworker-select" data-sync-toggle="send_coworker">
                                    <option value="0">ارسال نشود</option>
                                    <option value="1">ارسال شود</option>
                                </select>
                            </label>
                            <input type="hidden" name="send_coworker" id="f-send-coworker" value="0">
                            <label class="inline-label">کلید واژه
                                <select id="kw-coworker">
                                    @foreach($placeholders as $token => $label)
                                        <option value="{{ $token }}">{{ $label }} — {{ $token }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="button" class="btn btn-secondary" data-insert-into="f-coworker-message" data-kw="kw-coworker">درج</button>
                        </div>
                    </div>
                    <textarea name="coworker_message_template" id="f-coworker-message" rows="4" placeholder="متن پیامک همکار (اختیاری)..."></textarea>
                    <div class="sms-counters">
                        <span>طول پیام: <strong id="len-coworker">0</strong></span>
                        <span>تعداد پیامک: <strong id="parts-coworker">0</strong></span>
                    </div>
                </div>

                <div class="accept-row accept-row-3">
                    @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال در منوی تغییر وضعیت قبض', 'checked' => true, 'id' => 'tg_is_active'])
                    @include('partials.toggle', ['name' => 'on_create', 'label' => 'ارسال بعد از ثبت قبض جدید', 'checked' => false, 'id' => 'tg_on_create'])
                    @include('partials.toggle', ['name' => 'on_price', 'label' => 'ارسال وقتی مبلغ مشخص شد', 'checked' => false, 'id' => 'tg_on_price'])
                    @include('partials.toggle', ['name' => 'is_hidden', 'label' => 'مخفی از چیپ وضعیت', 'checked' => false, 'id' => 'tg_is_hidden'])
                </div>

                <div class="sms-actions">
                    <button class="btn btn-primary" type="submit" id="btn-add">افزودن</button>
                    <button class="btn btn-secondary" type="submit" id="btn-edit" disabled>ویرایش</button>
                    <button class="btn" type="button" id="btn-hide" disabled>مخفی کردن</button>
                    <button class="btn btn-danger" type="button" id="btn-delete" disabled>حذف</button>
                    <button class="btn btn-ghost" type="button" id="btn-reset">پاک کردن فرم</button>
                </div>
            </form>

            <form method="POST" id="sms-hide-form" class="hidden">@csrf</form>
            <form method="POST" id="sms-delete-form" class="hidden" onsubmit="return confirm('حذف این وضعیت؟')">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
            </form>
        </div>

        <div class="ws-pane" data-ws-pane="gateway">
            <div class="panel">
                <h3>تنظیمات ارسال SMS (نیازپرداز)</h3>
                <form method="POST" action="{{ route('sms-statuses.gateway') }}">
                    @csrf
                    <div class="accept-row accept-row-2">
                        <div><label>نام کاربری</label><input type="text" name="niazpardaz_username" value="{{ $sms['username'] }}"></div>
                        <div><label>رمز</label><input type="password" name="niazpardaz_password" placeholder="در صورت تغییر"></div>
                        <div><label>API Key</label><input type="text" name="niazpardaz_api_key" value="{{ $sms['api_key'] }}"></div>
                        <div><label>شماره فرستنده</label><input type="text" name="niazpardaz_from" value="{{ $sms['from'] }}"></div>
                    </div>
                    <div class="actions"><button class="btn btn-primary" type="submit">ذخیره تنظیمات ارسال</button></div>
                </form>
            </div>
            <div class="panel" style="margin-top:8px;">
                <h3>تست ارسال</h3>
                <form method="POST" action="{{ route('sms-statuses.test') }}">
                    @csrf
                    <div class="accept-row accept-row-3">
                        <div><label>موبایل تست</label><input type="text" name="test_phone" placeholder="09xxxxxxxxx" required></div>
                        <div style="display:flex;align-items:end;"><button class="btn btn-secondary" type="submit">ارسال پیامک تست</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="ws-pane" data-ws-pane="logs">
            <div class="panel">
                <h3>گزارش ارسال پیامک</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr><th>زمان</th><th>گیرنده</th><th>قبض</th><th>وضعیت</th><th>مخاطب</th><th>نتیجه</th><th>متن</th></tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ jalali_like($log->created_at) }}</td>
                                <td>{{ $log->phone }}</td>
                                <td>
                                    @if($log->reception)
                                        <a href="{{ route('receptions.show', $log->reception) }}">{{ $log->reception->ticket_no }}</a>
                                    @else — @endif
                                </td>
                                <td>{{ $log->rule?->title ?: $log->status_key }}</td>
                                <td>{{ $log->audience === 'coworker' ? 'همکار' : 'مشتری' }}</td>
                                <td>{!! $log->ok ? '<span class="pill pill-ok">موفق</span>' : '<span class="pill pill-off">ناموفق</span>' !!}</td>
                                <td style="max-width:260px;white-space:pre-wrap;font-size:12px;">{{ $log->message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">ارسالی ثبت نشده.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('sms-editor-form');
    if (!form) return;
    var method = document.getElementById('editor-method');
    var selectedId = null;
    var selectedRow = null;

    function smsParts(len) {
        if (len <= 0) return 0;
        if (len <= 70) return 1;
        return Math.ceil(len / 67);
    }
    function updateCounters() {
        var c = (document.getElementById('f-message').value || '').length;
        var w = (document.getElementById('f-coworker-message').value || '').length;
        document.getElementById('len-customer').textContent = c;
        document.getElementById('parts-customer').textContent = smsParts(c);
        document.getElementById('len-coworker').textContent = w;
        document.getElementById('parts-coworker').textContent = smsParts(w);
    }
    function setToggle(name, on) {
        var field = form.querySelector('[name="' + name + '"][data-toggle-input], [data-toggle-field] [name="' + name + '"][type="checkbox"]');
        // find toggle field by checkbox name
        var cb = form.querySelector('input[type="checkbox"][name="' + name + '"]');
        if (!cb) return;
        var wrap = cb.closest('[data-toggle-field]');
        if (!wrap) { cb.checked = !!on; return; }
        var btn = wrap.querySelector('[data-toggle-btn]');
        cb.checked = !!on;
        if (btn) {
            btn.classList.toggle('is-on', !!on);
            btn.classList.toggle('is-off', !on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            var st = btn.querySelector('.toggle-state');
            if (st) st.textContent = on ? (btn.getAttribute('data-on-text') || 'روشن') : (btn.getAttribute('data-off-text') || 'خاموش');
        }
    }
    function syncSelect(selectId, hiddenId) {
        var sel = document.getElementById(selectId);
        var hid = document.getElementById(hiddenId);
        if (!sel || !hid) return;
        hid.value = sel.value;
        sel.addEventListener('change', function () { hid.value = sel.value; });
    }
    function syncSendMode() {
        var sel = document.getElementById('f-send-mode');
        var hid = document.getElementById('f-auto-send');
        if (!sel || !hid) return;
        hid.value = sel.value === 'always' ? '1' : '0';
    }
    var sendModeSel = document.getElementById('f-send-mode');
    if (sendModeSel) sendModeSel.addEventListener('change', syncSendMode);
    syncSendMode();
    syncSelect('f-coworker-select', 'f-send-coworker');

    function resetForm() {
        selectedId = null;
        selectedRow = null;
        form.action = @json(route('sms-statuses.store'));
        method.value = 'POST';
        document.getElementById('editor-heading').textContent = 'افزودن وضعیت جدید';
        document.getElementById('btn-add').disabled = false;
        document.getElementById('btn-edit').disabled = true;
        document.getElementById('btn-hide').disabled = true;
        document.getElementById('btn-delete').disabled = true;
        document.getElementById('f-title').value = '';
        document.getElementById('f-summary').value = '';
        document.getElementById('f-status-key').value = '';
        document.getElementById('f-sort').value = '10';
        document.getElementById('f-stage').value = 'run';
        document.getElementById('f-result').value = 'active';
        document.getElementById('f-color').value = 'blue';
        document.getElementById('f-description').value = '';
        document.getElementById('f-message').value = 'سلام {customer_name}\nدستگاه شما ({device}) — سریال {serial}\nخرابی: {fault}\nوضعیت: {status}\nقبض: {ticket_no}';
        document.getElementById('f-coworker-message').value = '';
        document.getElementById('f-send-mode').value = 'always';
        document.getElementById('f-auto-send').value = '1';
        document.getElementById('f-coworker-select').value = '0';
        document.getElementById('f-send-coworker').value = '0';
        setToggle('is_active', true);
        setToggle('on_create', false);
        setToggle('on_price', false);
        setToggle('is_hidden', false);
        document.querySelectorAll('#sms-status-table tr.is-selected').forEach(function (r) { r.classList.remove('is-selected'); });
        updateCounters();
    }

    function fillFromRow(row) {
        selectedRow = row;
        selectedId = row.getAttribute('data-rule-id');
        form.action = row.getAttribute('data-update-url');
        method.value = 'PUT';
        document.getElementById('editor-heading').textContent = 'ویرایش وضعیت';
        document.getElementById('btn-add').disabled = true;
        document.getElementById('btn-edit').disabled = false;
        document.getElementById('btn-hide').disabled = false;
        document.getElementById('btn-delete').disabled = false;
        document.getElementById('f-title').value = row.getAttribute('data-title') || '';
        document.getElementById('f-summary').value = row.getAttribute('data-summary') || '';
        document.getElementById('f-status-key').value = row.getAttribute('data-status-key') || '';
        document.getElementById('f-sort').value = row.getAttribute('data-sort') || '0';
        document.getElementById('f-stage').value = row.getAttribute('data-stage') || 'run';
        document.getElementById('f-result').value = row.getAttribute('data-result') || 'active';
        document.getElementById('f-color').value = row.getAttribute('data-color') || 'blue';
        document.getElementById('f-description').value = row.getAttribute('data-description') || '';
        document.getElementById('f-message').value = row.getAttribute('data-message') || '';
        document.getElementById('f-coworker-message').value = row.getAttribute('data-coworker-message') || '';
        var mode = row.getAttribute('data-send-mode') || (row.getAttribute('data-auto-send') === '1' ? 'always' : 'never');
        var cow = row.getAttribute('data-send-coworker') === '1' ? '1' : '0';
        document.getElementById('f-send-mode').value = mode;
        document.getElementById('f-auto-send').value = mode === 'always' ? '1' : '0';
        document.getElementById('f-coworker-select').value = cow;
        document.getElementById('f-send-coworker').value = cow;
        setToggle('is_active', row.getAttribute('data-active') === '1');
        setToggle('on_create', row.getAttribute('data-on-create') === '1');
        setToggle('on_price', row.getAttribute('data-on-price') === '1');
        setToggle('is_hidden', row.getAttribute('data-hidden') === '1');
        document.querySelectorAll('#sms-status-table tr.is-selected').forEach(function (r) { r.classList.remove('is-selected'); });
        row.classList.add('is-selected');
        updateCounters();
    }

    document.querySelectorAll('#sms-status-table tbody tr').forEach(function (row) {
        row.addEventListener('click', function () { fillFromRow(row); });
    });

    document.querySelectorAll('[data-insert-into]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var ta = document.getElementById(btn.getAttribute('data-insert-into'));
            var kw = document.getElementById(btn.getAttribute('data-kw'));
            if (!ta || !kw) return;
            var token = kw.value;
            var start = ta.selectionStart || ta.value.length;
            var end = ta.selectionEnd || ta.value.length;
            ta.value = ta.value.slice(0, start) + token + ta.value.slice(end);
            ta.focus();
            ta.setSelectionRange(start + token.length, start + token.length);
            updateCounters();
        });
    });

    ['f-message', 'f-coworker-message'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updateCounters);
    });

    document.getElementById('btn-reset').addEventListener('click', resetForm);
    document.getElementById('btn-add').addEventListener('click', function () {
        // ensure add mode
        if (selectedId) resetForm();
        method.value = 'POST';
        form.action = @json(route('sms-statuses.store'));
    });
    document.getElementById('btn-edit').addEventListener('click', function () {
        if (!selectedId) return;
        method.value = 'PUT';
        form.action = selectedRow.getAttribute('data-update-url');
    });
    document.getElementById('btn-hide').addEventListener('click', function () {
        if (!selectedRow) return;
        var f = document.getElementById('sms-hide-form');
        f.action = selectedRow.getAttribute('data-hide-url');
        f.submit();
    });
    document.getElementById('btn-delete').addEventListener('click', function () {
        if (!selectedRow) return;
        var f = document.getElementById('sms-delete-form');
        f.action = selectedRow.getAttribute('data-delete-url');
        f.submit();
    });

    updateCounters();
})();
</script>
@endpush
