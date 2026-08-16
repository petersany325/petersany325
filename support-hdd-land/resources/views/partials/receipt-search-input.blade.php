@php
    $name = $name ?? 'q';
    $value = (string) ($value ?? '');
    $prefix = $prefix ?? 'T-20N';
    $id = $id ?? 'receipt-search-'.preg_replace('/[^a-z0-9_\-]/i', '-', $name);
    $required = (bool) ($required ?? false);
    $autofocus = (bool) ($autofocus ?? false);
    $placeholder = $placeholder ?? '1000';
    $hint = $hint ?? 'فقط ادامه شماره را بزنید';
    $allowFree = (bool) ($allowFree ?? true);
    $inputmode = $inputmode ?? ($allowFree ? 'text' : 'numeric');
    $barcode = (bool) ($barcode ?? true);
    $display = $value;
    if ($display !== '' && strncasecmp($display, $prefix, strlen($prefix)) === 0) {
        $display = substr($display, strlen($prefix));
    }
@endphp
<div class="receipt-search-input" data-receipt-prefix-wrap data-prefix="{{ $prefix }}" data-allow-free="{{ $allowFree ? '1' : '0' }}">
    <span class="receipt-search-prefix" aria-hidden="true">{{ $prefix }}</span>
    <input type="text"
           id="{{ $id }}"
           value="{{ $display }}"
           placeholder="{{ $placeholder }}"
           dir="ltr"
           inputmode="{{ $inputmode }}"
           autocomplete="off"
           data-receipt-suffix
           data-ascii-en
           @if($barcode) data-barcode @endif
           @if($required) required @endif
           @if($autofocus) autofocus @endif
           aria-label="جستجوی قبض، نام یا موبایل">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-receipt-full>
</div>
@if($hint)
    <div class="receipt-search-hint muted">{{ $hint }}</div>
@endif
