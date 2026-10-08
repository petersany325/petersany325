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
class ManageStoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?string $navigationLabel = 'Store settings';

    protected static ?string $title = 'Store settings';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'store_name' => Setting::getValue('store_name', 'EK Electronics'),
            'tagline' => Setting::getValue('tagline', 'Innovation. Integrity. Impact.'),
            'phone' => Setting::getValue('phone', '+27 10 500 2140'),
            'email' => Setting::getValue('email', 'info@ekelectronics.co.za'),
            'address' => Setting::getValue('address', "Unit 15, Ground floor, Lone Creek Office Building D\n21 Mac-Mac Road and Howick Close\nWaterfall Business Park, Midrand, 1685"),
            'hours' => Setting::getValue('hours', 'Mon–Fri 08:00–17:00 SAST'),
            'currency' => Setting::getValue('currency', 'ZAR'),
            'vat_rate' => Setting::getValue('vat_rate', '15'),
            'free_shipping_min' => Setting::getValue('free_shipping_min', '2500'),
            'shipping_note' => Setting::getValue('shipping_note', 'Courier nationwide from Midrand. Collection available at Waterfall Business Park.'),
            'hero_headline' => Setting::getValue('hero_headline', 'Hard drives & components, refurbished with integrity.'),
            'hero_sub' => Setting::getValue('hero_sub', 'Enterprise storage, memory, boards, and professional data recovery from Midrand.'),
            'hero_cta_label' => Setting::getValue('hero_cta_label', 'Shop catalogue'),
            'hero_cta_url' => Setting::getValue('hero_cta_url', '/shop'),
            'shop_enabled' => Setting::bool('shop_enabled', true),
            'checkout_enabled' => Setting::bool('checkout_enabled', true),
            'show_prices' => Setting::bool('show_prices', true),
            'low_stock_threshold' => Setting::getValue('low_stock_threshold', '10'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Brand & contact')->schema([
                TextInput::make('store_name')->required(),
                TextInput::make('tagline')->required(),
                TextInput::make('phone')->required(),
                TextInput::make('email')->email()->required(),
                TextInput::make('hours')->required(),
                Textarea::make('address')->rows(3)->required()->columnSpanFull(),
            ])->columns(2),
            Section::make('Commerce')->schema([
                Toggle::make('shop_enabled')->label('Shop enabled'),
                Toggle::make('checkout_enabled')->label('Checkout enabled'),
                Toggle::make('show_prices')->label('Show prices publicly'),
                TextInput::make('currency')->required()->maxLength(8),
                TextInput::make('vat_rate')->numeric()->suffix('%')->required(),
                TextInput::make('free_shipping_min')->numeric()->prefix('R'),
                TextInput::make('low_stock_threshold')->numeric()->required(),
                Textarea::make('shipping_note')->rows(2)->columnSpanFull(),
            ])->columns(3),
            Section::make('Home hero')->schema([
                TextInput::make('hero_headline')->required()->columnSpanFull(),
                Textarea::make('hero_sub')->rows(2)->required()->columnSpanFull(),
                TextInput::make('hero_cta_label')->required(),
                TextInput::make('hero_cta_url')->required(),
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
        Setting::many($this->form->getState());
        Notification::make()->title('Store settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save store settings')->submit('save');
    }
}
