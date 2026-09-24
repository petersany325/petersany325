{{-- نوار میانبر شخصی (آیکونی) — سمت راست صفحه --}}
@php
    $dockItems = \App\Support\StaffShortcutDock::forUser(auth()->user());
    $dockCatalog = \App\Support\StaffShortcutDock::catalog(auth()->user());
    $dockIds = collect($dockItems)->pluck('id')->all();
@endphp
<aside class="sc-dock" id="staff-shortcut-dock" aria-label="میانبرهای کارمند" data-sc-dock
       data-save-url="{{ route('profile.shortcuts') }}"
       data-max="{{ \App\Support\StaffShortcutDock::MAX }}">
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
            <span class="sc-dock-lbl">میانبر</span>
        </button>
    </div>
</aside>

<div class="sc-dock-editor" id="staff-shortcut-editor" hidden data-sc-editor>
    <div class="sc-dock-editor-backdrop" data-sc-close-editor></div>
    <div class="sc-dock-editor-panel" role="dialog" aria-label="ویرایش میانبرها">
        <div class="sc-dock-editor-head">
            <div>
                <strong>میانبرهای من</strong>
                <div class="muted" style="font-size:11px;">تا {{ \App\Support\StaffShortcutDock::MAX }} میانبر — برای تعمیرکار و کارمند</div>
            </div>
            <button type="button" class="btn btn-ghost" data-sc-close-editor>بستن</button>
        </div>
        <div class="sc-dock-editor-body">
            <div class="sc-dock-selected" data-sc-selected>
                @forelse($dockItems as $item)
                    <div class="sc-dock-chip" data-sc-chip data-sc-id="{{ $item['id'] }}">
                        <span class="sc-dock-ico tone-{{ $item['tone'] }}">{{ $item['mark'] }}</span>
                        <span class="sc-dock-chip-text">
                            <strong>{{ $item['label'] }}</strong>
                            <small>{{ $item['group'] }}</small>
                        </span>
                        <button type="button" class="sc-dock-chip-x" data-sc-remove title="حذف">×</button>
                    </div>
                @empty
                    <p class="muted" data-sc-empty>هنوز میانبری نیست — از فهرست زیر اضافه کنید.</p>
                @endforelse
            </div>
            <div class="sc-dock-catalog-head">
                <strong>افزودن از منوها</strong>
                <input type="search" data-sc-filter placeholder="جستجوی منو..." autocomplete="off">
            </div>
            <div class="sc-dock-catalog" data-sc-catalog>
                @foreach($dockCatalog as $row)
                    @php $pinned = in_array($row['id'], $dockIds, true); @endphp
                    <button type="button"
                            class="sc-dock-cat-item {{ $pinned ? 'is-pinned' : '' }}"
                            data-sc-add
                            data-sc-id="{{ $row['id'] }}"
                            data-sc-label="{{ $row['label'] }}"
                            data-sc-mark="{{ $row['mark'] }}"
                            data-sc-hint="{{ $row['hint'] }}"
                            data-sc-group="{{ $row['group'] }}"
                            data-sc-tone="{{ $row['tone'] }}"
                            data-sc-url="{{ route($row['route']) }}"
                            data-menu-label="{{ $row['label'] }} {{ $row['group'] }} {{ $row['hint'] }}"
                            @disabled($pinned)>
                        <span class="sc-dock-ico tone-{{ $row['tone'] }}">{{ $row['mark'] }}</span>
                        <span>
                            <strong>{{ $row['label'] }}</strong>
                            <small>{{ $row['group'] }}@if($row['hint'] !== '') — {{ $row['hint'] }}@endif</small>
                        </span>
                        <span class="sc-dock-cat-state">{{ $pinned ? '✓' : '+' }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <div class="sc-dock-editor-foot">
            <button type="button" class="btn btn-ghost" data-sc-reset>بازگشت به پیش‌فرض</button>
            <button type="button" class="btn btn-primary" data-sc-save>ذخیره میانبرها</button>
        </div>
    </div>
</div>
