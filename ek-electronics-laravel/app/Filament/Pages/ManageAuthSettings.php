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
class ManageAuthSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Login & portals';

    protected static ?string $title = 'Customer & staff login settings';

    protected static ?int $navigationSort = 20;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'customer_login_enabled' => Setting::bool('customer_login_enabled', true),
            'customer_register_enabled' => Setting::bool('customer_register_enabled', true),
            'customer_portal_enabled' => Setting::bool('customer_portal_enabled', true),
            'customer_login_heading' => Setting::getValue('customer_login_heading', 'Customer sign in'),
            'customer_login_blurb' => Setting::getValue('customer_login_blurb', 'Track orders, open support tickets, and manage your EK account.'),
            'customer_register_blurb' => Setting::getValue('customer_register_blurb', 'Create a free customer account to follow purchases and recovery tickets.'),
            'staff_login_enabled' => Setting::bool('staff_login_enabled', true),
            'staff_portal_enabled' => Setting::bool('staff_portal_enabled', true),
            'staff_login_heading' => Setting::getValue('staff_login_heading', 'Staff portal'),
            'staff_login_blurb' => Setting::getValue('staff_login_blurb', 'Operations desk for orders, tickets, recovery jobs, and accounting.'),
            'require_phone_on_register' => Setting::bool('require_phone_on_register', false),
            'welcome_message' => Setting::getValue('welcome_message', 'Welcome to EK Electronics.'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer login & register')->schema([
                Toggle::make('customer_login_enabled')->label('Customer login enabled'),
                Toggle::make('customer_register_enabled')->label('Show register link / allow registration'),
                Toggle::make('customer_portal_enabled')->label('Customer account portal enabled'),
                Toggle::make('require_phone_on_register')->label('Require phone on registration'),
                TextInput::make('customer_login_heading')->required(),
                Textarea::make('customer_login_blurb')->rows(2)->required()->columnSpanFull(),
                Textarea::make('customer_register_blurb')->rows(2)->required()->columnSpanFull(),
                TextInput::make('welcome_message')->required()->columnSpanFull(),
            ])->columns(2),
            Section::make('Staff login')->schema([
                Toggle::make('staff_login_enabled')->label('Staff login enabled'),
                Toggle::make('staff_portal_enabled')->label('Staff portal enabled'),
                TextInput::make('staff_login_heading')->required(),
                Textarea::make('staff_login_blurb')->rows(2)->required()->columnSpanFull(),
            ])->columns(2)->description('Administrators still use /admin (Filament). Staff use /staff.'),
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
        Notification::make()->title('Login & portal settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save login settings')->submit('save');
    }
}
