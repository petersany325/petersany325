<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageWhatsappApi extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'WhatsApp API';

    protected static ?string $title = 'WhatsApp API activation';

    protected string $view = 'filament.pages.manage-whatsapp-api';

    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'whatsapp_api_enabled' => Setting::bool('whatsapp_api_enabled', false),
            'whatsapp_api_provider' => Setting::getValue('whatsapp_api_provider', 'wa_me'),
            'whatsapp_api_mode' => Setting::getValue('whatsapp_api_mode', 'sandbox'),
            'whatsapp_phone_number_id' => Setting::getValue('whatsapp_phone_number_id', ''),
            'whatsapp_business_account_id' => Setting::getValue('whatsapp_business_account_id', ''),
            'whatsapp_api_access_token' => Setting::getValue('whatsapp_api_access_token', ''),
            'whatsapp_api_version' => Setting::getValue('whatsapp_api_version', 'v21.0'),
            'whatsapp_api_base_url' => Setting::getValue('whatsapp_api_base_url', 'https://graph.facebook.com'),
            'whatsapp_webhook_verify_token' => Setting::getValue('whatsapp_webhook_verify_token', 'ek-wa-verify'),
            'whatsapp_webhook_secret' => Setting::getValue('whatsapp_webhook_secret', ''),
            'whatsapp_webhook_enabled' => Setting::bool('whatsapp_webhook_enabled', false),
            'whatsapp_api_send_orders' => Setting::bool('whatsapp_api_send_orders', true),
            'whatsapp_api_send_tickets' => Setting::bool('whatsapp_api_send_tickets', true),
            'whatsapp_api_send_contact' => Setting::bool('whatsapp_api_send_contact', false),
            'whatsapp_api_fallback_wame' => Setting::bool('whatsapp_api_fallback_wame', true),
            'whatsapp_api_notes' => Setting::getValue('whatsapp_api_notes', ''),
            'webhook_url_display' => url('/webhooks/whatsapp'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $webhookUrl = url('/webhooks/whatsapp');

        return $schema->components([
            Section::make('API activation')->schema([
                Toggle::make('whatsapp_api_enabled')
                    ->label('Activate WhatsApp Cloud / Business API')
                    ->helperText('When on, the system can send messages via the configured provider instead of only opening wa.me links.')
                    ->live(),
                Select::make('whatsapp_api_provider')
                    ->label('Provider')
                    ->options([
                        'wa_me' => 'wa.me links only (no Cloud API)',
                        'meta' => 'Meta WhatsApp Cloud API',
                        'twilio' => 'Twilio WhatsApp API',
                        'custom' => 'Custom HTTP webhook sender',
                    ])
                    ->required()
                    ->native(false)
                    ->live(),
                Select::make('whatsapp_api_mode')
                    ->label('Mode')
                    ->options([
                        'sandbox' => 'Sandbox / test',
                        'live' => 'Live production',
                    ])
                    ->required()
                    ->native(false),
                Toggle::make('whatsapp_api_fallback_wame')
                    ->label('Fall back to wa.me if API send fails')
                    ->default(true),
            ])->columns(2),

            Section::make('Meta Cloud API credentials')
                ->description('From Meta Developer → WhatsApp → API Setup. Webhook URL: '.$webhookUrl)
                ->visible(fn (Get $get): bool => in_array($get('whatsapp_api_provider'), ['meta', 'custom'], true))
                ->schema([
                    TextInput::make('whatsapp_phone_number_id')->label('Phone number ID'),
                    TextInput::make('whatsapp_business_account_id')->label('WhatsApp Business Account ID (WABA)'),
                    TextInput::make('whatsapp_api_access_token')
                        ->label('Permanent access token')
                        ->password()
                        ->revealable()
                        ->columnSpanFull(),
                    TextInput::make('whatsapp_api_version')->label('Graph API version')->placeholder('v21.0'),
                    TextInput::make('whatsapp_api_base_url')->label('API base URL')->url()->columnSpanFull(),
                ])->columns(2),

            Section::make('Twilio credentials')
                ->visible(fn (Get $get): bool => $get('whatsapp_api_provider') === 'twilio')
                ->schema([
                    TextInput::make('whatsapp_phone_number_id')->label('Twilio Account SID')->dehydrated(),
                    TextInput::make('whatsapp_api_access_token')->label('Twilio Auth Token')->password()->revealable()->dehydrated(),
                    TextInput::make('whatsapp_business_account_id')->label('Twilio WhatsApp From (whatsapp:+27…)')->dehydrated(),
                    TextInput::make('whatsapp_api_base_url')->label('API base URL')
                        ->default('https://api.twilio.com')
                        ->dehydrated(),
                ])->columns(2),

            Section::make('Webhook (incoming chat)')->schema([
                Toggle::make('whatsapp_webhook_enabled')->label('Enable webhook receiver'),
                TextInput::make('whatsapp_webhook_verify_token')
                    ->label('Verify token')
                    ->helperText('Must match the token you enter in Meta / provider webhook settings.'),
                TextInput::make('whatsapp_webhook_secret')
                    ->label('App secret (optional signature check)')
                    ->password()
                    ->revealable(),
                TextInput::make('webhook_url_display')
                    ->label('Callback URL (copy to Meta)')
                    ->default($webhookUrl)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('When to send via API')->schema([
                Toggle::make('whatsapp_api_send_orders')->label('Send shop order alerts via API'),
                Toggle::make('whatsapp_api_send_tickets')->label('Send ticket alerts via API'),
                Toggle::make('whatsapp_api_send_contact')->label('Send contact-form alerts via API'),
                Textarea::make('whatsapp_api_notes')
                    ->label('Internal notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([
                    $this->getTestAction(),
                    $this->getSaveFormAction(),
                ])]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        unset($data['webhook_url_display']);
        Setting::many($data);
        Notification::make()->title('WhatsApp API settings saved')->success()->send();
    }

    public function testConnection(): void
    {
        $result = WhatsApp::testApiConnection();
        if ($result['ok']) {
            Notification::make()->title('API connection OK')->body($result['message'])->success()->send();
        } else {
            Notification::make()->title('API connection failed')->body($result['message'])->danger()->send();
        }
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save API settings')->submit('save')->color('primary');
    }

    protected function getTestAction(): Action
    {
        return Action::make('test')
            ->label('Test API connection')
            ->color('gray')
            ->action('testConnection');
    }
}
