<x-filament-panels::page>
    <form wire:submit="save" class="ek-home-editor">
        <div class="ek-home-grid">
            <div class="ek-home-form">
                <x-filament::section>
                    <x-slot name="heading">Page title</x-slot>
                    <label class="ek-field">Browser title
                        <input type="text" wire:model.live.debounce.250ms="data.home_meta_title">
                    </label>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Hero</x-slot>
                    <label class="ek-check"><input type="checkbox" wire:model.live="data.home_show_hero"> Show hero</label>
                    <label class="ek-field">Kicker
                        <input type="text" wire:model.live.debounce.250ms="data.hero_kicker">
                    </label>
                    <label class="ek-field">Headline
                        <input type="text" wire:model.live.debounce.250ms="data.hero_headline">
                    </label>
                    <label class="ek-field">Subtext
                        <textarea rows="3" wire:model.live.debounce.250ms="data.hero_sub"></textarea>
                    </label>
                    <label class="ek-field">Image path or URL
                        <input type="text" wire:model.live.debounce.250ms="data.hero_image" placeholder="assets/img/hero.jpg">
                    </label>
                    <div class="ek-two">
                        <label class="ek-field">Button 1 label<input type="text" wire:model.live.debounce.250ms="data.hero_cta_label"></label>
                        <label class="ek-field">Button 1 URL<input type="text" wire:model.live.debounce.250ms="data.hero_cta_url"></label>
                        <label class="ek-field">Button 2 label<input type="text" wire:model.live.debounce.250ms="data.hero_cta2_label"></label>
                        <label class="ek-field">Button 2 URL<input type="text" wire:model.live.debounce.250ms="data.hero_cta2_url"></label>
                    </div>
                    <label class="ek-check"><input type="checkbox" wire:model.live="data.hero_show_whatsapp"> Show WhatsApp button</label>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Stats strip</x-slot>
                    <label class="ek-check"><input type="checkbox" wire:model.live="data.home_show_stats"> Show stats</label>
                    @foreach ([1, 2, 3, 4] as $n)
                        <div class="ek-two">
                            <label class="ek-field">Stat {{ $n }} value<input type="text" wire:model.live.debounce.250ms="data.stat_{{ $n }}_value"></label>
                            <label class="ek-field">Stat {{ $n }} label<input type="text" wire:model.live.debounce.250ms="data.stat_{{ $n }}_label"></label>
                        </div>
                    @endforeach
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Bestsellers</x-slot>
                    <label class="ek-check"><input type="checkbox" wire:model.live="data.home_show_bestsellers"> Show bestsellers</label>
                    <label class="ek-field">Title<input type="text" wire:model.live.debounce.250ms="data.bestsellers_title"></label>
                    <label class="ek-field">Intro<textarea rows="2" wire:model.live.debounce.250ms="data.bestsellers_lede"></textarea></label>
                    <label class="ek-field">How many products<input type="number" min="1" max="24" wire:model.live.debounce.250ms="data.bestsellers_count"></label>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Lab block</x-slot>
                    <label class="ek-check"><input type="checkbox" wire:model.live="data.home_show_lab"> Show lab block</label>
                    <label class="ek-field">Kicker<input type="text" wire:model.live.debounce.250ms="data.lab_kicker"></label>
                    <label class="ek-field">Heading<input type="text" wire:model.live.debounce.250ms="data.lab_heading"></label>
                    <label class="ek-field">Body<textarea rows="3" wire:model.live.debounce.250ms="data.lab_body"></textarea></label>
                    <label class="ek-field">Image path or URL<input type="text" wire:model.live.debounce.250ms="data.lab_image"></label>
                    <div class="ek-two">
                        <label class="ek-field">Button 1 label<input type="text" wire:model.live.debounce.250ms="data.lab_cta_label"></label>
                        <label class="ek-field">Button 1 URL<input type="text" wire:model.live.debounce.250ms="data.lab_cta_url"></label>
                        <label class="ek-field">Button 2 label<input type="text" wire:model.live.debounce.250ms="data.lab_cta2_label"></label>
                        <label class="ek-field">Button 2 URL<input type="text" wire:model.live.debounce.250ms="data.lab_cta2_url"></label>
                    </div>
                </x-filament::section>

                <div class="ek-save-row">
                    <x-filament::button type="submit">Save homepage</x-filament::button>
                    <a class="ek-open" href="{{ url('/') }}" target="_blank" rel="noopener">Open live homepage</a>
                </div>
            </div>

            <aside class="ek-home-preview" wire:key="home-preview">
                <div class="ek-preview-bar">Live preview — updates as you type. Save to publish.</div>
                <div class="ek-preview-frame">
                    @if($data['home_show_hero'] ?? true)
                        <div class="ek-pv-hero">
                            <small>{{ $data['hero_kicker'] ?? '' }}</small>
                            <h2>{{ $data['hero_headline'] ?? '' }}</h2>
                            <p>{{ $data['hero_sub'] ?? '' }}</p>
                            <div class="ek-pv-ctas">
                                <span>{{ $data['hero_cta_label'] ?? '' }}</span>
                                <span>{{ $data['hero_cta2_label'] ?? '' }}</span>
                                @if($data['hero_show_whatsapp'] ?? true)<span class="wa">WhatsApp</span>@endif
                            </div>
                        </div>
                    @endif
                    @if($data['home_show_stats'] ?? true)
                        <div class="ek-pv-stats">
                            @foreach ([1, 2, 3, 4] as $n)
                                <div><b>{{ $data['stat_'.$n.'_value'] ?? '' }}</b><span>{{ $data['stat_'.$n.'_label'] ?? '' }}</span></div>
                            @endforeach
                        </div>
                    @endif
                    @if($data['home_show_bestsellers'] ?? true)
                        <div class="ek-pv-block">
                            <h3>{{ $data['bestsellers_title'] ?? '' }}</h3>
                            <p>{{ $data['bestsellers_lede'] ?? '' }}</p>
                            <small>Showing {{ (int) ($data['bestsellers_count'] ?? 8) }} featured products</small>
                        </div>
                    @endif
                    @if($data['home_show_lab'] ?? true)
                        <div class="ek-pv-block">
                            <small>{{ $data['lab_kicker'] ?? '' }}</small>
                            <h3>{{ $data['lab_heading'] ?? '' }}</h3>
                            <p>{{ $data['lab_body'] ?? '' }}</p>
                            <div class="ek-pv-ctas">
                                <span>{{ $data['lab_cta_label'] ?? '' }}</span>
                                <span>{{ $data['lab_cta2_label'] ?? '' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </form>

    <style>
        .ek-home-grid { display: grid; grid-template-columns: minmax(280px, 1fr) minmax(280px, 1fr); gap: 16px; align-items: start; }
        @media (max-width: 980px) { .ek-home-grid { grid-template-columns: 1fr; } }
        .ek-home-form { display: grid; gap: 12px; }
        .ek-field { display: grid; gap: 4px; font-size: 13px; font-weight: 700; color: #23497c; margin: 8px 0; }
        .ek-field input, .ek-field textarea { font-weight: 500; color: #111; border: 1px solid #8299b4; padding: 8px 10px; width: 100%; background: #fff; }
        .ek-two { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .ek-check { display: flex; gap: 8px; align-items: center; font-weight: 700; margin: 6px 0 10px; }
        .ek-save-row { display: flex; gap: 12px; align-items: center; }
        .ek-open { color: #23497c; font-weight: 800; }
        .ek-home-preview { position: sticky; top: 12px; border: 2px solid #0b198c; background: #d1d1e1; }
        .ek-preview-bar { background: #5c7099; color: #fff; font-weight: 800; font-size: 12px; padding: 8px 10px; }
        .ek-preview-frame { padding: 12px; display: grid; gap: 10px; }
        .ek-pv-hero, .ek-pv-block { background: #0f172a; color: #fff; padding: 16px; }
        .ek-pv-hero small, .ek-pv-block small { color: #7dd3fc; letter-spacing: .08em; text-transform: uppercase; font-size: 11px; }
        .ek-pv-hero h2, .ek-pv-block h3 { margin: 6px 0; }
        .ek-pv-ctas { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .ek-pv-ctas span { background: #1d4ed8; color: #fff; font-size: 12px; font-weight: 800; padding: 4px 8px; }
        .ek-pv-ctas span.wa { background: #25d366; color: #053b1d; }
        .ek-pv-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .ek-pv-stats div { background: #fff; border: 1px solid #0b198c; padding: 8px; }
        .ek-pv-stats b { display: block; }
        .ek-pv-stats span, .ek-pv-block p { font-size: 13px; }
        .ek-pv-block { background: #fff; color: #111; border: 1px solid #0b198c; }
    </style>
</x-filament-panels::page>
