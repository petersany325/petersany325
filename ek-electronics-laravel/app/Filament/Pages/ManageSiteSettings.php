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
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?string $navigationLabel = 'Quick contact';

    protected static ?string $title = 'Quick contact details';

    protected static ?int $navigationSort = 2;

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
            Section::make('Contact shortcuts')
                ->description('Full store, footer, WhatsApp, and login settings are in their own admin pages.')
                ->schema([
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
        Setting::many($this->form->getState());
        Notification::make()->title('Contact details saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save')->submit('save');
    }
}
