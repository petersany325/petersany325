@php
    $name = $name ?? 'permissions[]';
    $value = $value ?? '';
    $label = $label ?? '';
    $checked = (bool) ($checked ?? false);
    $on = $on ?? 'روشن';
    $off = $off ?? 'خاموش';
    $id = $id ?? ('tg_perm_'.preg_replace('/[^a-zA-Z0-9_]+/', '_', (string) $value).'_'.uniqid());
@endphp
<div class="toggle-field" data-toggle-field data-toggle-optional>
    <div class="toggle-label">{{ $label }}</div>
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
            aria-label="{{ $label }}">
        <span class="toggle-track">
            <span class="toggle-knob"></span>
        </span>
        <span class="toggle-state">{{ $checked ? $on : $off }}</span>
    </button>
</div>
