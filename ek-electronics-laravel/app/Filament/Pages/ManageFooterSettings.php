<?php

namespace App\Filament\Pages;

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
class ManageFooterSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Footer settings';

    protected static ?string $title = 'Footer settings';

    protected static ?int $navigationSort = 3;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'footer_about' => Setting::getValue('footer_about', 'Hard drive refurbishment, secure erasure, data recovery, and computer components from Midrand.'),
            'footer_col1_title' => Setting::getValue('footer_col1_title', 'Customer services'),
            'footer_col2_title' => Setting::getValue('footer_col2_title', 'Shop'),
            'footer_col3_title' => Setting::getValue('footer_col3_title', 'Contact'),
            'footer_legal' => Setting::getValue('footer_legal', '© {year} EK Electronics · ekelectronics.co.za'),
            'footer_facebook' => Setting::getValue('footer_facebook', ''),
            'footer_instagram' => Setting::getValue('footer_instagram', ''),
            'footer_tiktok' => Setting::getValue('footer_tiktok', ''),
            'footer_x' => Setting::getValue('footer_x', ''),
            'footer_show_socials' => Setting::bool('footer_show_socials', true),
            'footer_show_whatsapp' => Setting::bool('footer_show_whatsapp', true),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Columns')->schema([
                Textarea::make('footer_about')->rows(3)->required()->columnSpanFull(),
                TextInput::make('footer_col1_title')->required(),
                TextInput::make('footer_col2_title')->required(),
                TextInput::make('footer_col3_title')->required(),
                TextInput::make('footer_legal')->required()->helperText('Use {year} for the current year.')->columnSpanFull(),
            ])->columns(3),
            Section::make('Social & extras')->schema([
                Toggle::make('footer_show_socials')->label('Show social icons'),
                Toggle::make('footer_show_whatsapp')->label('Show WhatsApp floating button'),
                TextInput::make('footer_facebook')->url()->label('Facebook URL'),
                TextInput::make('footer_instagram')->url()->label('Instagram URL'),
                TextInput::make('footer_tiktok')->url()->label('TikTok URL'),
                TextInput::make('footer_x')->url()->label('X / Twitter URL'),
            ])->columns(2)->description('Menu links under each column are managed in Content → Menus (footer locations).'),
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
        Setting::many($this->form->getState());
        Notification::make()->title('Footer settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save footer settings')->submit('save');
    }
}
