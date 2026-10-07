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
class ManageWhatsappSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'WhatsApp settings';

    protected static ?string $title = 'WhatsApp settings';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'whatsapp_number' => Setting::getValue('whatsapp_number', '27105002140'),
            'whatsapp_display_name' => Setting::getValue('whatsapp_display_name', 'EK Operations'),
            'whatsapp_enabled' => Setting::bool('whatsapp_enabled', true),
            'whatsapp_fab_enabled' => Setting::bool('whatsapp_fab_enabled', true),
            'whatsapp_default_message' => Setting::getValue('whatsapp_default_message', 'Hi EK Electronics, I need help with a drive.'),
            'whatsapp_order_template' => Setting::getValue('whatsapp_order_template', "New order {number} from {name}\nPhone: {phone}\nShip to: {city}\n\n{lines}\n\nTotal: R {total}\nPlease confirm stock and courier."),
            'whatsapp_ticket_template' => Setting::getValue('whatsapp_ticket_template', 'New ticket {number}: {subject}'),
            'whatsapp_business_hours' => Setting::getValue('whatsapp_business_hours', 'Mon–Fri 08:00–17:00 SAST'),
            'whatsapp_away_message' => Setting::getValue('whatsapp_away_message', 'Thanks for messaging EK Electronics. We will reply during business hours.'),
            'whatsapp_notify_orders' => Setting::bool('whatsapp_notify_orders', true),
            'whatsapp_notify_tickets' => Setting::bool('whatsapp_notify_tickets', true),
            'whatsapp_notify_contact' => Setting::bool('whatsapp_notify_contact', true),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Channel')->schema([
                Toggle::make('whatsapp_enabled')->label('WhatsApp channel enabled'),
                Toggle::make('whatsapp_fab_enabled')->label('Show floating chat button on storefront'),
                TextInput::make('whatsapp_number')->required()->helperText('Digits only with country code, e.g. 27105002140'),
                TextInput::make('whatsapp_display_name')->required(),
                TextInput::make('whatsapp_business_hours')->required(),
                Textarea::make('whatsapp_default_message')->rows(2)->required()->columnSpanFull(),
                Textarea::make('whatsapp_away_message')->rows(2)->columnSpanFull(),
            ])->columns(2),
            Section::make('Templates')->schema([
                Textarea::make('whatsapp_order_template')->rows(6)->required()
                    ->helperText('Placeholders: {number} {name} {phone} {city} {lines} {total}')->columnSpanFull(),
                Textarea::make('whatsapp_ticket_template')->rows(2)->required()
                    ->helperText('Placeholders: {number} {subject}')->columnSpanFull(),
            ]),
            Section::make('Notifications')->schema([
                Toggle::make('whatsapp_notify_orders')->label('Log / open WhatsApp on new shop orders'),
                Toggle::make('whatsapp_notify_tickets')->label('Log WhatsApp on new customer tickets'),
                Toggle::make('whatsapp_notify_contact')->label('Log WhatsApp on contact form'),
            ])->columns(1),
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
        Notification::make()->title('WhatsApp settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save WhatsApp settings')->submit('save');
    }
}
