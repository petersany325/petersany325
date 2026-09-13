<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>چاپ برچسب — {{ $payload['human'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --lw: {{ (int) $settings['width_mm'] }}mm;
            --lh: {{ (int) $settings['height_mm'] }}mm;
            --gap: {{ (int) $settings['gap_mm'] }}mm;
            --pad: {{ (int) $settings['margin_mm'] }}mm;
            --fs: {{ (int) $settings['font_size'] }}pt;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Vazirmatn, Tahoma, sans-serif;
            background: #eef1f5;
            color: #111;
        }
        .toolbar {
            position: sticky; top: 0; z-index: 5;
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            padding: 10px 12px;
            background: #fff;
            border-bottom: 1px solid #dbe1ea;
        }
        .toolbar .muted { color: #64748b; font-size: 12px; }
        .toolbar select, .toolbar input {
            font: inherit; font-size: 12px;
            padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 8px;
            background: #fff;
        }
        .btn {
            font: inherit; font-size: 12px; font-weight: 700;
            border: 0; border-radius: 8px; padding: 8px 12px; cursor: pointer;
            background: #0f766e; color: #fff; text-decoration: none; display: inline-flex;
        }
        .btn.secondary { background: #334155; }
        .btn.ghost { background: #e2e8f0; color: #0f172a; }
        .sheet {
            padding: 16px;
            display: flex; flex-wrap: wrap; gap: var(--gap);
            justify-content: flex-start;
        }
        .label {
            width: var(--lw);
            height: var(--lh);
            background: #fff;
            border: 1px dashed #94a3b8;
            padding: var(--pad);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            @if($settings['barcode_position'] === 'top')
            justify-content: flex-start;
            @elseif($settings['barcode_position'] === 'bottom')
            justify-content: flex-end;
            @else
            justify-content: center;
            @endif
            align-items: stretch;
            page-break-inside: avoid;
        }
        .label .shop { font-size: calc(var(--fs) - 1pt); font-weight: 800; text-align: center; line-height: 1.2; }
        .label .t1 { font-size: var(--fs); font-weight: 800; text-align: center; line-height: 1.15; }
        .label .t2, .label .t3 { font-size: calc(var(--fs) - 1pt); text-align: center; color: #334155; line-height: 1.15; }
        .label .bc { display: flex; justify-content: center; align-items: center; margin: 2px 0; }
        .label svg { max-width: 100%; height: auto; }
        .label .human { font-size: calc(var(--fs) - 1pt); text-align: center; letter-spacing: .04em; direction: ltr; }
        .hint-box {
            margin: 0 16px 12px; padding: 10px 12px; border-radius: 10px;
            background: #ecfeff; color: #155e75; font-size: 12px;
        }
        @media print {
            body { background: #fff; }
            .toolbar, .hint-box, .no-print { display: none !important; }
            .sheet { padding: 0; gap: var(--gap); }
            .label { border: 0; }
            @page {
                @if($settings['size_key'] === 'A4_sheet')
                size: A4;
                margin: 8mm;
                @else
                size: {{ (int) $settings['width_mm'] }}mm {{ (int) $settings['height_mm'] }}mm;
                margin: 0;
                @endif
            }
        }
    </style>
</head>
<body>
@php
    $layout = $settings['layout'];
    $showShop = $settings['show_shop'] && $layout !== 'barcode_only';
    $showText = $settings['show_text'] && $layout !== 'barcode_only';
@endphp

<div class="toolbar no-print">
    <a class="btn ghost" href="{{ $backUrl }}">بازگشت</a>
    <button class="btn" type="button" onclick="window.print()">چاپ برچسب</button>
    <label class="muted">تعداد
        <input type="number" id="qty" min="1" max="500" value="{{ $copies }}" style="width:70px">
    </label>
    <label class="muted">حالت چاپ
        <select id="mode">
            @foreach($modes as $key => $mode)
                <option value="{{ $key }}" @selected(($settings['mode'] ?? '') === $key)>{{ $mode['label'] }}</option>
            @endforeach
        </select>
    </label>
    <label class="muted">جای بارکد
        <select id="pos">
            @foreach(\App\Support\LabelPrintSettings::POSITIONS as $k => $lab)
                <option value="{{ $k }}" @selected($settings['barcode_position'] === $k)>{{ $lab }}</option>
            @endforeach
        </select>
    </label>
    @if(!empty($stockQty))
        <a class="btn secondary" href="{{ $printUrlBase }}?stock=1&qty={{ max(1, $stockQty) }}&mode={{ $settings['mode'] }}">چاپ به تعداد موجودی ({{ number_format($stockQty) }})</a>
    @endif
    <button class="btn secondary" type="button" id="apply-opts">اعمال</button>
    <span class="muted">{{ $copies }} برچسب · {{ $settings['width_mm'] }}×{{ $settings['height_mm'] }}mm · {{ $settings['symbology'] }}</span>
</div>

@if($settings['printer_name'])
    <div class="hint-box no-print">
        پرینتر مخصوص بارکد تعریف‌شده: <strong>{{ $settings['printer_name'] }}</strong>
        — در پنجره چاپ سیستم، همین پرینتر را انتخاب کنید (مرورگر نمی‌تواند پرینتر را اجباری کند).
    </div>
@else
    <div class="hint-box no-print">
        چاپ با پرینتر معمولی یا حرارتی از طریق پنجره چاپ مرورگر انجام می‌شود. برای انتخاب پیش‌فرض، در تنظیمات ← برچسب / بارکد نام پرینتر را ثبت کنید.
    </div>
@endif

<div class="sheet" id="sheet">
    @for($i = 0; $i < $copies; $i++)
        <div class="label">
            @if($showShop && in_array($layout, ['title_barcode','title_barcode_text','serial_device','part_stock'], true))
                <div class="shop">{{ $payload['title'] }}</div>
            @endif
            @if($showText && in_array($layout, ['title_barcode','title_barcode_text','serial_device','part_stock','compact_shelf'], true))
                <div class="t1">{{ $payload['line1'] }}</div>
            @endif
            @if($showText && in_array($layout, ['title_barcode_text','serial_device','part_stock'], true) && $payload['line2'])
                <div class="t2">{{ $payload['line2'] }}</div>
            @endif
            <div class="bc"><svg class="barcode"></svg></div>
            @if($showText)
                <div class="human">{{ $payload['human'] }}</div>
            @endif
            @if($showText && $layout === 'title_barcode_text' && $payload['line3'])
                <div class="t3">{{ $payload['line3'] }}</div>
            @endif
            @if($showText && in_array($layout, ['serial_device','part_stock'], true) && $payload['line3'])
                <div class="t3">{{ $payload['line3'] }}</div>
            @endif
        </div>
    @endfor
</div>

<script>
(function () {
    var format = @json($settings['symbology']);
    var value = @json($payload['code']);
    var opts = {
        format: format,
        displayValue: false,
        margin: 0,
        height: Math.max(24, Math.min(70, {{ (int) $settings['height_mm'] }} * 1.6)),
        width: 1.6,
        background: '#ffffff'
    };
    document.querySelectorAll('svg.barcode').forEach(function (el) {
        try { JsBarcode(el, value, opts); }
        catch (e) {
            try { JsBarcode(el, String(value).replace(/\D+/g, '') || '0', Object.assign({}, opts, { format: 'CODE128' })); }
            catch (e2) { el.parentNode.innerHTML = '<div class="human">خطا در بارکد</div>'; }
        }
    });

    var base = @json($printUrlBase);
    function rebuild() {
        var qty = document.getElementById('qty').value || 1;
        var mode = document.getElementById('mode').value;
        var pos = document.getElementById('pos').value;
        var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'qty=' + encodeURIComponent(qty)
            + '&mode=' + encodeURIComponent(mode)
            + '&pos=' + encodeURIComponent(pos);
        window.location.href = url;
    }
    document.getElementById('apply-opts').addEventListener('click', rebuild);

    @if($settings['auto_print'])
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 350);
    });
    @endif
})();
</script>
</body>
</html>
