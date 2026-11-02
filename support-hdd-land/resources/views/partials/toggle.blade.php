@php
    $name = $name ?? 'toggle';
    $label = $label ?? '';
    $checked = (bool) ($checked ?? false);
    $on = $on ?? 'روشن';
    $off = $off ?? 'خاموش';
    $value = $value ?? '1';
    $offValue = $offValue ?? '0';
    $id = $id ?? ('tg_'.preg_replace('/[^a-zA-Z0-9_]+/', '_', $name).'_'.uniqid());
@endphp
<div class="toggle-field" data-toggle-field>
    @if($label !== '')
        <div class="toggle-label">{{ $label }}</div>
    @endif
    <input type="hidden" name="{{ $name }}" value="{{ $offValue }}" data-toggle-off>
    <input type="checkbox"
           id="{{ $id }}"
           class="toggle-checkbox"
           name="{{ $name }}"
           value="{{ $value }}"
           @checked($checked)
           data-toggle-input>
    <button type="button"
            class="toggle-switch {{ $checked ? 'is-on' : 'is-off' }}"
            data-toggle-btn
            data-on-text="{{ $on }}"
            data-off-text="{{ $off }}"
            aria-pressed="{{ $checked ? 'true' : 'false' }}"
            aria-label="{{ $label !== '' ? $label : $name }}">
        <span class="toggle-track">
            <span class="toggle-knob"></span>
        </span>
        <span class="toggle-state">{{ $checked ? $on : $off }}</span>
    </button>
</div>
