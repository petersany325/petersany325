<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">All admin menus (English)</x-slot>
            <x-slot name="description">
                Use this control center if a sidebar group is collapsed. Every major module is linked below.
            </x-slot>
            <p class="text-sm text-gray-600">
                Storefront menus are managed under <strong>Content → Menus</strong>.
                Customer login: <a class="text-primary-600 underline" href="/login" target="_blank">/login</a>
                · Staff: <a class="text-primary-600 underline" href="/login?portal=staff" target="_blank">/login?portal=staff</a>
                · Register: <a class="text-primary-600 underline" href="/register" target="_blank">/register</a>
            </p>
        </x-filament::section>

        @foreach ($this->sections() as $heading => $links)
            <x-filament::section>
                <x-slot name="heading">{{ $heading }}</x-slot>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($links as $link)
                        <a href="{{ url($link['url']) }}"
                           class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-400 hover:shadow">
                            <div class="font-semibold text-gray-900">{{ $link['label'] }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ $link['hint'] }}</div>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
