<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Chat settings';

    protected static ?string $title = 'WhatsApp chat settings';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $quick = Setting::getValue('whatsapp_quick_replies', "Stock check\nCourier quote\nData recovery\nTalk to sales");
        $this->form->fill([
            'whatsapp_number' => Setting::getValue('whatsapp_number', '27105002140'),
            'whatsapp_display_name' => Setting::getValue('whatsapp_display_name', 'EK Operations'),
            'whatsapp_enabled' => Setting::bool('whatsapp_enabled', true),
            'whatsapp_fab_enabled' => Setting::bool('whatsapp_fab_enabled', true),
            'whatsapp_default_message' => Setting::getValue('whatsapp_default_message', 'Hi EK Electronics, I need help with a drive.'),
            'whatsapp_order_template' => Setting::getValue('whatsapp_order_template', "New order {number} from {name}\nPhone: {phone}\nShip to: {city}\n\n{lines}\n\nTotal: R {total}\nPlease confirm stock and courier."),
            'whatsapp_ticket_template' => Setting::getValue('whatsapp_ticket_template', 'New ticket {number}: {subject}'),
            'whatsapp_invoice_template' => Setting::getValue('whatsapp_invoice_template', "Invoice {number} for {name}\nAmount: R {amount}\nDue: {due}\nPay via EFT and reply with proof."),
            'whatsapp_recovery_template' => Setting::getValue('whatsapp_recovery_template', "Recovery job {number} update: {stage}\nClient: {name}"),
            'whatsapp_business_hours' => Setting::getValue('whatsapp_business_hours', 'Mon–Fri 08:00–17:00 SAST'),
            'whatsapp_timezone' => Setting::getValue('whatsapp_timezone', 'Africa/Johannesburg'),
            'whatsapp_hours_start' => Setting::getValue('whatsapp_hours_start', '08:00'),
            'whatsapp_hours_end' => Setting::getValue('whatsapp_hours_end', '17:00'),
            'whatsapp_away_message' => Setting::getValue('whatsapp_away_message', 'Thanks for messaging EK Electronics. We will reply during business hours.'),
            'whatsapp_welcome_message' => Setting::getValue('whatsapp_welcome_message', 'Welcome to EK Electronics. How can we help — shop, recovery, or an existing order?'),
            'whatsapp_offline_message' => Setting::getValue('whatsapp_offline_message', 'We are offline right now. Leave your number and we will WhatsApp you back.'),
            'whatsapp_notify_orders' => Setting::bool('whatsapp_notify_orders', true),
            'whatsapp_notify_tickets' => Setting::bool('whatsapp_notify_tickets', true),
            'whatsapp_notify_contact' => Setting::bool('whatsapp_notify_contact', true),
            'whatsapp_notify_invoices' => Setting::bool('whatsapp_notify_invoices', true),
            'whatsapp_chat_position' => Setting::getValue('whatsapp_chat_position', 'bottom-right'),
            'whatsapp_chat_color' => Setting::getValue('whatsapp_chat_color', '#25d366'),
            'whatsapp_chat_label' => Setting::getValue('whatsapp_chat_label', 'WhatsApp us'),
            'whatsapp_chat_subtitle' => Setting::getValue('whatsapp_chat_subtitle', 'Typically replies in minutes during business hours'),
            'whatsapp_chat_auto_open' => Setting::bool('whatsapp_chat_auto_open', false),
            'whatsapp_chat_sound' => Setting::bool('whatsapp_chat_sound', true),
            'whatsapp_chat_show_agent' => Setting::bool('whatsapp_chat_show_agent', true),
            'whatsapp_chat_agent_name' => Setting::getValue('whatsapp_chat_agent_name', 'EK Support'),
            'whatsapp_chat_prechat_enabled' => Setting::bool('whatsapp_chat_prechat_enabled', false),
            'whatsapp_chat_require_name' => Setting::bool('whatsapp_chat_require_name', false),
            'whatsapp_chat_require_email' => Setting::bool('whatsapp_chat_require_email', false),
            'whatsapp_department_default' => Setting::getValue('whatsapp_department_default', 'sales'),
            'whatsapp_quick_replies' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $quick) ?: []))),
            'whatsapp_signature' => Setting::getValue('whatsapp_signature', "\n— EK Electronics Midrand"),
            'whatsapp_read_receipts_note' => Setting::getValue('whatsapp_read_receipts_note', 'Mark conversations answered in the WhatsApp desk after you reply.'),
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
                TextInput::make('whatsapp_chat_agent_name')->label('Agent display name')->required(),
                Toggle::make('whatsapp_chat_show_agent')->label('Show agent name in chat bubble'),
            ])->columns(2),

            Section::make('Chat widget')->schema([
                Select::make('whatsapp_chat_position')->options([
                    'bottom-right' => 'Bottom right',
                    'bottom-left' => 'Bottom left',
                ])->required()->native(false),
                TextInput::make('whatsapp_chat_color')->label('Accent colour')->required(),
                TextInput::make('whatsapp_chat_label')->required(),
                TextInput::make('whatsapp_chat_subtitle')->required()->columnSpanFull(),
                Toggle::make('whatsapp_chat_auto_open')->label('Auto-open chat prompt once per session'),
                Toggle::make('whatsapp_chat_sound')->label('Play soft sound on new desk message (admin)'),
                Toggle::make('whatsapp_chat_prechat_enabled')->label('Ask for details before opening WhatsApp'),
                Toggle::make('whatsapp_chat_require_name')->label('Require name in pre-chat'),
                Toggle::make('whatsapp_chat_require_email')->label('Require email in pre-chat'),
                Select::make('whatsapp_department_default')->options([
                    'sales' => 'Sales',
                    'support' => 'Support',
                    'recovery' => 'Data recovery',
                    'accounts' => 'Accounts',
                ])->required()->native(false),
                TagsInput::make('whatsapp_quick_replies')
                    ->label('Quick reply chips')
                    ->placeholder('Add a chip')
                    ->helperText('Shown as shortcuts under the chat prompt')
                    ->columnSpanFull(),
            ])->columns(3),

            Section::make('Business hours & auto replies')->schema([
                TextInput::make('whatsapp_business_hours')->required()->columnSpanFull(),
                TextInput::make('whatsapp_timezone')->required(),
                TextInput::make('whatsapp_hours_start')->label('Weekday start')->required(),
                TextInput::make('whatsapp_hours_end')->label('Weekday end')->required(),
                Textarea::make('whatsapp_welcome_message')->rows(2)->required()->columnSpanFull(),
                Textarea::make('whatsapp_default_message')->label('Default click-to-chat text')->rows(2)->required()->columnSpanFull(),
                Textarea::make('whatsapp_away_message')->rows(2)->required()->columnSpanFull(),
                Textarea::make('whatsapp_offline_message')->rows(2)->required()->columnSpanFull(),
                Textarea::make('whatsapp_signature')->rows(2)->columnSpanFull(),
            ])->columns(3),

            Section::make('Message templates')->schema([
                Textarea::make('whatsapp_order_template')->rows(5)->required()
                    ->helperText('Placeholders: {number} {name} {phone} {city} {lines} {total}')->columnSpanFull(),
                Textarea::make('whatsapp_ticket_template')->rows(2)->required()
                    ->helperText('Placeholders: {number} {subject}')->columnSpanFull(),
                Textarea::make('whatsapp_invoice_template')->rows(3)->required()
                    ->helperText('Placeholders: {number} {name} {amount} {due}')->columnSpanFull(),
                Textarea::make('whatsapp_recovery_template')->rows(2)->required()
                    ->helperText('Placeholders: {number} {stage} {name}')->columnSpanFull(),
            ]),

            Section::make('Notifications')->schema([
                Toggle::make('whatsapp_notify_orders')->label('Orders'),
                Toggle::make('whatsapp_notify_tickets')->label('Tickets'),
                Toggle::make('whatsapp_notify_contact')->label('Contact form'),
                Toggle::make('whatsapp_notify_invoices')->label('Invoices'),
                Textarea::make('whatsapp_read_receipts_note')->label('Desk reminder')->rows(2)->columnSpanFull(),
            ])->columns(4),
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
        $data = $this->form->getState();
        if (isset($data['whatsapp_quick_replies']) && is_array($data['whatsapp_quick_replies'])) {
            $data['whatsapp_quick_replies'] = implode("\n", $data['whatsapp_quick_replies']);
        }
        Setting::many($data);
        Notification::make()->title('Chat settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save chat settings')->submit('save');
    }
}
