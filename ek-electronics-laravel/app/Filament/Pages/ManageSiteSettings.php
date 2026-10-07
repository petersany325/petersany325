<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
class ManageSiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'phone' => Setting::getValue('phone', '+27 10 500 2140'),
            'email' => Setting::getValue('email', 'info@ekelectronics.co.za'),
            'whatsapp_number' => Setting::getValue('whatsapp_number', '27105002140'),
            'address' => Setting::getValue('address', "Unit 15, Ground floor, Lone Creek Office Building D\n21 Mac-Mac Road and Howick Close\nWaterfall Business Park, Midrand, 1685"),
            'tagline' => Setting::getValue('tagline', 'Innovation. Integrity. Impact.'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact & WhatsApp')->schema([
                TextInput::make('phone')->required(),
                TextInput::make('email')->email()->required(),
                TextInput::make('whatsapp_number')->helperText('Digits only with country code')->required(),
                TextInput::make('tagline')->required(),
                Textarea::make('address')->rows(4)->required()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveFormAction()]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            Setting::setValue($key, is_string($value) ? $value : (string) $value);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save settings')->submit('save');
    }
}
