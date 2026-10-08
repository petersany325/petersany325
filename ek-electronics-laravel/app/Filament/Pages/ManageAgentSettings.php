<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageAgentSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Agents';

    protected static ?string $navigationLabel = 'Agent page';

    protected static ?string $title = 'Agent page settings';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'agents_menu_label' => Setting::getValue('agents_menu_label', 'Agents'),
            'agents_show_in_menu' => Setting::bool('agents_show_in_menu', true),
            'agents_page_kicker' => Setting::getValue('agents_page_kicker', 'Authorized representation'),
            'agents_page_title' => Setting::getValue('agents_page_title', 'Brands we represent'),
            'agents_page_intro' => Setting::getValue('agents_page_intro', 'EK Electronics represents specialist data-recovery brands for customers in South Africa. Each appointment is listed here, with the territory, what we supply, and how to reach the Midrand laboratory.'),
            'agents_empty_text' => Setting::getValue('agents_empty_text', 'No active representations are published yet.'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu')->schema([
                TextInput::make('agents_menu_label')->label('Header label')->required()->maxLength(80),
                Toggle::make('agents_show_in_menu')->label('Show Agents in the header menu'),
            ])->columns(2),
            Section::make('Agents page')->schema([
                TextInput::make('agents_page_kicker')->label('Kicker')->required()->maxLength(80),
                TextInput::make('agents_page_title')->label('Heading')->required()->maxLength(160),
                Textarea::make('agents_page_intro')->label('Introduction')->rows(4)->required()->columnSpanFull(),
                Textarea::make('agents_empty_text')->label('Empty state')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([$this->getSaveFormAction()])]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        Setting::many($state);
        $this->syncMenu((string) $state['agents_menu_label'], (bool) $state['agents_show_in_menu']);
        Notification::make()->title('Agent page saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save agent page')->submit('save');
    }

    private function syncMenu(string $label, bool $visible): void
    {
        $item = Menu::query()->where('url', '/agents')->where('location', 'header')->first();
        if (! $item && ! $visible) {
            return;
        }
        if (! $item) {
            $item = new Menu([
                'location' => 'header',
                'url' => '/agents',
                'target' => '_self',
                'sort_order' => ((int) Menu::query()->where('location', 'header')->max('sort_order')) + 1,
            ]);
        }
        $item->label = $label;
        $item->is_active = $visible;
        $item->save();
    }
}
