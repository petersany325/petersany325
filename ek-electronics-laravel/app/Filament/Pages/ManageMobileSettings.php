<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\MobileWeb;
use BackedEnum;
use Filament\Actions\Action;
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
class ManageMobileSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'Mobile';

    protected static ?string $navigationLabel = 'Mobile web';

    protected static ?string $title = 'Mobile web';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(MobileWeb::formState());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mobile layout')
                ->description('One setup for every phone and small tablet. iPhone, Android, and tablets at or below the width use the same header, menu, and bottom bar. Desktop keeps the single-line menu.')
                ->schema([
                    Toggle::make('mobile_enabled')->label('Use the mobile web layout')->default(true),
                    TextInput::make('mobile_breakpoint')
                        ->label('Apply on screens up to (px)')
                        ->numeric()
                        ->minValue(480)
                        ->maxValue(1200)
                        ->required()
                        ->helperText('900 covers phones and small tablets. Raise it if larger tablets should use the mobile layout too.'),
                ])->columns(2),
            Section::make('Header on mobile')
                ->schema([
                    Toggle::make('mobile_show_topbar')->label('Show the top contact bar')->default(true),
                    Toggle::make('mobile_show_search')->label('Show search under the logo')->default(true),
                    Toggle::make('mobile_hide_tagline')->label('Hide the tagline next to the logo')->default(true),
                    Toggle::make('mobile_sticky_header')->label('Keep the header pinned while scrolling')->default(true),
                    TextInput::make('mobile_menu_label')->label('Menu button label')->required()->maxLength(40),
                ])->columns(2),
            Section::make('Bottom bar')
                ->description('Shown on every device that uses the mobile layout. Turn a destination off to remove it from the bar.')
                ->schema([
                    Toggle::make('mobile_bottom_nav')->label('Show the bottom bar')->default(true)->columnSpanFull(),
                    Toggle::make('mobile_bar_home')->label('Home'),
                    TextInput::make('mobile_bar_home_label')->label('Home label')->maxLength(24),
                    Toggle::make('mobile_bar_shop')->label('Shop'),
                    TextInput::make('mobile_bar_shop_label')->label('Shop label')->maxLength(24),
                    Toggle::make('mobile_bar_services')->label('Services'),
                    TextInput::make('mobile_bar_services_label')->label('Services label')->maxLength(24),
                    Toggle::make('mobile_bar_agents')->label('Agents'),
                    TextInput::make('mobile_bar_agents_label')->label('Agents label')->maxLength(24),
                    Toggle::make('mobile_bar_cart')->label('Cart'),
                    TextInput::make('mobile_bar_cart_label')->label('Cart label')->maxLength(24),
                    Toggle::make('mobile_lift_whatsapp')->label('Keep WhatsApp above the bottom bar')->default(true)->columnSpanFull(),
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
        $state = $this->form->getState();
        $state['mobile_breakpoint'] = MobileWeb::breakpoint($state['mobile_breakpoint'] ?? 900);
        Setting::many($state);
        Notification::make()->title('Mobile web settings saved')->success()->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label('Save')->submit('save');
    }
}
