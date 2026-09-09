@php
    $name = $name ?? 'date';
    $raw = $value ?? '';
    $required = (bool) ($required ?? false);
    $id = $id ?? null;
    $attrs = $attrs ?? '';
    $placeholder = $placeholder ?? '1404/05/16';
    $class = trim('jalali-date '.($class ?? ''));
    if ($raw === null || $raw === '') {
        $display = '';
    } elseif (is_string($raw) && preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}/', $raw)) {
        $display = $raw;
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
       style="text-align:left;{{ $style ?? '' }}"
       @if($required) required @endif
       {!! $attrs !!}>
