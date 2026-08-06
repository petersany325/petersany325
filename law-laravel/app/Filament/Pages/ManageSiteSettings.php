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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    protected static ?string $title = 'تنظیمات مدرن سایت و وب‌اپ';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم و حساب';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $defaults = [
            'show_phone_in_header' => '1',
            'cta_text' => 'درخواست نوبت',
            'footer_copyright' => 'تمامی حقوق محفوظ است.',
            'footer_disclaimer' => 'محتوای سایت جنبه اطلاع‌رسانی دارد و جایگزین مشاوره اختصاصی نیست.',
            'pwa_enabled' => '1',
            'pwa_auto_mobile' => '1',
            'pwa_name' => 'مؤسسه حقوقی آریان',
            'pwa_short_name' => 'آریان',
            'pwa_description' => 'وکالت و مشاوره حقوقی تخصصی',
            'pwa_theme_color' => '#0a1628',
            'pwa_bg_color' => '#0a1628',
            'pwa_start_url' => '/app',
        ];

        $keys = [
            'site_name', 'site_tagline', 'phone', 'mobile', 'email', 'address', 'hours',
            'about_title', 'about_text', 'hero_lead',
            'footer_about', 'footer_copyright', 'footer_disclaimer',
            'social_instagram', 'social_linkedin', 'social_whatsapp',
            'cta_text', 'show_phone_in_header',
            'pwa_enabled', 'pwa_auto_mobile', 'pwa_name', 'pwa_short_name',
            'pwa_description', 'pwa_theme_color', 'pwa_bg_color', 'pwa_start_url',
        ];

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = Setting::get($key, $defaults[$key] ?? '');
        }

        $data['show_phone_in_header'] = ($data['show_phone_in_header'] ?? '1') === '1';
        $data['pwa_enabled'] = ($data['pwa_enabled'] ?? '1') === '1';
        $data['pwa_auto_mobile'] = ($data['pwa_auto_mobile'] ?? '1') === '1';
        $this->form->fill($data);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('settingsTabs')->tabs([
                Tab::make('برند و هیرو')->schema([
                    Section::make('هویت برند')->schema([
                        TextInput::make('site_name')->label('نام برند')->required(),
                        TextInput::make('site_tagline')->label('شعار'),
                        Textarea::make('hero_lead')->label('متن هیرو')->rows(3)->columnSpanFull(),
                        TextInput::make('cta_text')->label('متن دکمه اقدام')->helperText('مثلاً: درخواست نوبت'),
                        Toggle::make('show_phone_in_header')->label('نمایش تلفن در هدر دسکتاپ')->inline(false),
                    ])->columns(2),
                    Section::make('درباره ما')->schema([
                        TextInput::make('about_title')->label('عنوان')->columnSpanFull(),
                        Textarea::make('about_text')->label('متن')->rows(4)->columnSpanFull(),
                    ]),
                ]),
                Tab::make('تماس و فوتر')->schema([
                    Section::make('اطلاعات تماس')->schema([
                        TextInput::make('phone')->label('تلفن ثابت'),
                        TextInput::make('mobile')->label('موبایل'),
                        TextInput::make('email')->label('ایمیل')->email(),
                        TextInput::make('hours')->label('ساعات پاسخگویی'),
                        TextInput::make('address')->label('آدرس')->columnSpanFull(),
                    ])->columns(2),
                    Section::make('فوتر سایت')->schema([
                        Textarea::make('footer_about')->label('متن کوتاه فوتر')->rows(3)->columnSpanFull(),
                        Textarea::make('footer_disclaimer')->label('سلب مسئولیت حقوقی')->rows(2)->columnSpanFull(),
                        TextInput::make('footer_copyright')->label('متن کپی‌رایت')->columnSpanFull(),
                    ]),
                    Section::make('شبکه‌های اجتماعی')->schema([
                        TextInput::make('social_instagram')->label('اینستاگرام')->placeholder('https://instagram.com/...'),
                        TextInput::make('social_linkedin')->label('لینکدین')->placeholder('https://linkedin.com/...'),
                        TextInput::make('social_whatsapp')->label('واتساپ')->placeholder('https://wa.me/98...'),
                    ])->columns(3),
                ]),
                Tab::make('وب‌اپ موبایل')->schema([
                    Section::make('PWA / وب‌اپ')->description('با فعال‌سازی، بازدیدکنندگان موبایل به‌صورت خودکار وارد وب‌اپ می‌شوند و می‌توانند اپ را نصب کنند.')->schema([
                        Toggle::make('pwa_enabled')->label('فعال‌سازی وب‌اپ (PWA)')->inline(false),
                        Toggle::make('pwa_auto_mobile')->label('هدایت خودکار موبایل به /app')->helperText('کاربر می‌تواند از لینک «نسخه کامل سایت» برگردد.')->inline(false),
                        TextInput::make('pwa_name')->label('نام اپ'),
                        TextInput::make('pwa_short_name')->label('نام کوتاه'),
                        Textarea::make('pwa_description')->label('توضیح اپ')->rows(2)->columnSpanFull(),
                        TextInput::make('pwa_theme_color')->label('رنگ تم')->placeholder('#0a1628'),
                        TextInput::make('pwa_bg_color')->label('رنگ پس‌زمینه اسپلش')->placeholder('#0a1628'),
                        TextInput::make('pwa_start_url')->label('آدرس شروع')->helperText('معمولاً /app')->default('/app'),
                    ])->columns(2),
                ]),
            ])->columnSpanFull(),
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
        foreach (['show_phone_in_header', 'pwa_enabled', 'pwa_auto_mobile'] as $boolKey) {
            $data[$boolKey] = ! empty($data[$boolKey]) ? '1' : '0';
        }
        Setting::many($data);

        Notification::make()
            ->title('تنظیمات مدرن ذخیره شد')
            ->success()
            ->send();
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('ذخیره همه تنظیمات')
            ->submit('save');
    }
}
