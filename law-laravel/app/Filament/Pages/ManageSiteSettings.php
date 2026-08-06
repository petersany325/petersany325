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

    protected static ?string $navigationLabel = 'تنظیمات سایت';

    protected static ?string $title = 'تنظیمات سایت';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم و حساب';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = [
            'site_name', 'site_tagline', 'phone', 'address', 'hours',
            'about_title', 'about_text', 'hero_lead',
        ];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = Setting::get($key, '');
        }
        $this->form->fill($data);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('هویت برند')->schema([
                    TextInput::make('site_name')->label('نام برند')->required(),
                    TextInput::make('site_tagline')->label('شعار'),
                    Textarea::make('hero_lead')->label('متن هیرو')->rows(3)->columnSpanFull(),
                ])->columns(2),
                Section::make('اطلاعات تماس')->schema([
                    TextInput::make('phone')->label('تلفن'),
                    TextInput::make('hours')->label('ساعات پاسخگویی'),
                    TextInput::make('address')->label('آدرس')->columnSpanFull(),
                ])->columns(2),
                Section::make('درباره ما')->schema([
                    TextInput::make('about_title')->label('عنوان')->columnSpanFull(),
                    Textarea::make('about_text')->label('متن')->rows(4)->columnSpanFull(),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
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
        Setting::many($data);

        Notification::make()
            ->title('تنظیمات ذخیره شد')
            ->success()
            ->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('ذخیره تنظیمات')
            ->submit('save');
    }
}
