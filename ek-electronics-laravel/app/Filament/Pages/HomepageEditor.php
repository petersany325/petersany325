<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\HomepageContent;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class HomepageEditor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Homepage editor';

    protected static ?string $title = 'Homepage live editor';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.homepage-editor';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->data = HomepageContent::resolved();
        foreach (['home_show_hero', 'home_show_stats', 'home_show_bestsellers', 'home_show_lab', 'hero_show_whatsapp'] as $flag) {
            $this->data[$flag] = in_array((string) ($this->data[$flag] ?? '1'), ['1', 'true', 'yes', 'on'], true);
        }
    }

    public function save(): void
    {
        Setting::many($this->data);
        Notification::make()->title('Homepage saved — live site updated')->success()->send();
    }
}
