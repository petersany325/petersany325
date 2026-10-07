<x-filament-panels::page>
    <div class="ek-repair">
        <x-filament::section>
            <x-slot name="heading">Site repair</x-slot>
            <x-slot name="description">Clear caches, run database migrations, and tidy broken menu links. Safe to run more than once.</x-slot>
            <div class="ek-repair-actions">
                <x-filament::button wire:click="clearCache" color="gray">Clear cache</x-filament::button>
                <x-filament::button wire:click="fixDatabase" color="warning">Fix database</x-filament::button>
                <x-filament::button wire:click="optimizeSite">Optimize site</x-filament::button>
                <x-filament::button wire:click="fixAll" color="success">Fix everything</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Repair log</x-slot>
            <pre class="ek-repair-log">{{ $log }}</pre>
        </x-filament::section>
    </div>
    <style>
        .ek-repair { display: grid; gap: 14px; }
        .ek-repair-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .ek-repair-log {
            white-space: pre-wrap;
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px;
            min-height: 160px;
            font-size: 13px;
        }
    </style>
</x-filament-panels::page>
