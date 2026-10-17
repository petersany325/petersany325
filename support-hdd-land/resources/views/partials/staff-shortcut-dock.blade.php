{{-- نوار میانبر شخصی (آیکونی) — سمت راست صفحه --}}
@php
    $dockUser = auth()->user();
    $dockEnabled = $dockUser->ui_shortcuts_enabled !== false;
    $dockItems = \App\Support\StaffShortcutDock::forUser($dockUser);
    $dockCatalog = \App\Support\StaffShortcutDock::catalog($dockUser);
    $dockIds = collect($dockItems)->pluck('id')->all();
@endphp
<aside class="sc-dock {{ $dockEnabled ? '' : 'is-off' }}" id="staff-shortcut-dock" aria-label="میانبرهای کارمند"
       data-sc-dock
       data-save-url="{{ route('profile.shortcuts') }}"
       data-max="{{ \App\Support\StaffShortcutDock::MAX }}"
       data-enabled="{{ $dockEnabled ? '1' : '0' }}"
       @if(! $dockEnabled) hidden @endif>
    <div class="sc-dock-rail" data-sc-rail>
        @foreach($dockItems as $item)
            <a href="{{ $item['url'] }}"
               class="sc-dock-btn tone-{{ $item['tone'] }} {{ !empty($item['active']) ? 'is-on' : '' }}"
               data-sc-id="{{ $item['id'] }}"
               title="{{ $item['label'] }}{{ $item['hint'] !== '' ? ' — '.$item['hint'] : '' }}">
                <span class="sc-dock-ico">{{ $item['mark'] }}</span>
                <span class="sc-dock-lbl">{{ $item['label'] }}</span>
            </a>
        @endforeach
        <button type="button" class="sc-dock-btn sc-dock-edit" data-sc-open-editor title="افزودن / حذف میانبر">
            <span class="sc-dock-ico">＋</span>
            <span class="sc-dock-lbl">ویرایش</span>
        </button>
    </div>
</aside>

<div class="sc-dock-editor" id="staff-shortcut-editor" hidden data-sc-editor>
    <div class="sc-dock-editor-backdrop" data-sc-close-editor></div>
    <div class="sc-dock-editor-panel" role="dialog" aria-label="ویرایش میانبرها">
        <div class="sc-dock-editor-head">
            <div>
                <strong>میانبرهای من</strong>
                <div class="muted" style="font-size:11px;">
                    تیک روشن = نمایش در میانبر · حداکثر
                    <span data-sc-count>{{ count($dockIds) }}</span>/{{ \App\Support\StaffShortcutDock::MAX }}
                </div>
            </div>
            <button type="button" class="btn btn-ghost" data-sc-close-editor>بستن</button>
        </div>

        <div class="sc-dock-search-bar">
            <input type="search" class="sc-dock-search-input" data-sc-filter placeholder="جستجوی منو..." autocomplete="off">
        </div>

        <div class="sc-dock-editor-body">
            <div class="sc-dock-section-label">منوها — روشن/خاموش برای میانبر</div>
            <div class="sc-dock-catalog" data-sc-catalog>
                @foreach($dockCatalog as $row)
                    @php $pinned = in_array($row['id'], $dockIds, true); @endphp
                    <label class="sc-dock-cat-item {{ $pinned ? 'is-on' : '' }}"
                           data-sc-row
                           data-sc-id="{{ $row['id'] }}"
                           data-menu-label="{{ $row['label'] }} {{ $row['group'] }} {{ $row['hint'] }}">
                        <input type="checkbox"
                               class="sc-dock-row-check"
                               data-sc-pin
                               value="{{ $row['id'] }}"
                               {{ $pinned ? 'checked' : '' }}
                               aria-label="نمایش {{ $row['label'] }} در میانبر">
                        <span class="sc-dock-ico tone-{{ $row['tone'] }}">{{ $row['mark'] }}</span>
                        <span class="sc-dock-cat-text">
                            <strong>{{ $row['label'] }}</strong>
                            <small>{{ $row['group'] }}@if($row['hint'] !== '') — {{ $row['hint'] }}@endif</small>
                        </span>
                        <span class="sc-dock-row-switch" aria-hidden="true">
                            <span class="sc-dock-row-knob"></span>
                            <span class="sc-dock-row-on">ON</span>
                            <span class="sc-dock-row-off">OFF</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="sc-dock-editor-foot">
            <button type="button" class="btn btn-ghost" data-sc-reset>بازگشت به پیش‌فرض</button>
            <button type="button" class="btn btn-primary" data-sc-save>ذخیره میانبرها</button>
        </div>
    </div>
</div>
