@php
    $name = $name ?? 'date';
    $raw = $value ?? '';
    $required = (bool) ($required ?? false);
    $id = $id ?? null;
    $attrs = $attrs ?? '';
    $isJalali = app_calendar_is_jalali();
    $placeholder = $placeholder ?? ($isJalali ? '1404/05/16' : '2026/08/09');
    $class = trim('jalali-date '.($class ?? ''));
    if ($raw === null || $raw === '') {
        $display = '';
    } elseif (is_string($raw) && preg_match('/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}/', $raw)) {
        // Already a display-ish date; normalize separators and calendar via helper when possible.
        $parsed = parse_jalali_or_gregorian_date($raw);
        $display = $parsed ? jalali_input($parsed) : str_replace('-', '/', $raw);
    } else {
        $display = jalali_input($raw);
    }
@endphp
<input type="text"
       name="{{ $name }}"
       @if($id) id="{{ $id }}" @endif
       class="{{ $class }}"
       value="{{ $display }}"
       placeholder="{{ $placeholder }}"
       inputmode="numeric"
       autocomplete="off"
       dir="ltr"
       data-calendar="{{ $isJalali ? 'jalali' : 'gregorian' }}"
       style="text-align:left;{{ $style ?? '' }}"
       @if($required) required @endif
       {!! $attrs !!}>
