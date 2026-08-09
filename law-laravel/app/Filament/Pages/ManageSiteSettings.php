<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\NiazpardazSms;
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

    protected static ?string $navigationLabel = 'تنظیمات سایت و وب‌سرویس';

    protected static ?string $title = 'تنظیمات سایت، وب‌اپ و پیامک';

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
            'app_banner_size' => 'medium',
            'app_banner_height' => '42',
            'app_banner_position' => 'center 35%',
            'app_banner_show_lead' => '1',
            'home_services_limit' => '0',
            'home_team_limit' => '0',
            'home_faq_limit' => '0',
            'home_testimonials_limit' => '0',
            'home_posts_limit' => '0',
            'sms_enabled' => '0',
            'sms_on_appointment' => '1',
            'sms_on_confirm' => '1',
            'sms_on_advocacy' => '1',
            'sms_notify_admin' => '1',
            'sms_username' => '',
            'sms_password' => '',
            'sms_api_key' => '',
            'sms_from' => '',
            'sms_admin_phone' => '',
            'sms_tpl_appointment' => "{brand}\n{name} عزیز، درخواست نوبت شما ثبت شد.\nموضوع: {topic}\nتاریخ پیشنهادی: {date} ساعت {time}\nبه‌زودی هماهنگ می‌کنیم.",
            'sms_tpl_confirm' => "{brand}\n{name} عزیز، نوبت مشاوره شما تأیید شد.\nتاریخ: {date}\nساعت: {time}\nموضوع: {topic}",
            'sms_tpl_admin' => "نوبت جدید\n{name} | {phone}\n{topic}\n{date} {time}",
            'sms_tpl_advocacy' => "{brand}\n{name} عزیز، وکالت شما تأیید شد.\nموضوع: {subject}\nنوع پرونده: {case_type}\nمبلغ حق‌الوکاله: {fee}\nتاریخ قرارداد: {date}\nاز اعتماد شما سپاسگزاریم.",
        ];

        $keys = array_values(array_unique(array_merge([
            'site_name', 'site_tagline', 'phone', 'mobile', 'email', 'address', 'hours',
            'about_title', 'about_text', 'hero_lead',
            'footer_about', 'footer_copyright', 'footer_disclaimer',
            'social_instagram', 'social_linkedin', 'social_whatsapp',
            'cta_text', 'show_phone_in_header',
            'pwa_enabled', 'pwa_auto_mobile', 'pwa_name', 'pwa_short_name',
            'pwa_description', 'pwa_theme_color', 'pwa_bg_color', 'pwa_start_url',
            'app_banner_size', 'app_banner_height', 'app_banner_position', 'app_banner_show_lead',
            'home_services_limit', 'home_team_limit', 'home_faq_limit',
            'home_testimonials_limit', 'home_posts_limit',
            'sms_enabled', 'sms_on_appointment', 'sms_on_confirm', 'sms_on_advocacy', 'sms_notify_admin',
            'sms_username', 'sms_password', 'sms_api_key', 'sms_from', 'sms_admin_phone',
            'sms_tpl_appointment', 'sms_tpl_confirm', 'sms_tpl_admin', 'sms_tpl_advocacy',
        ], array_keys($defaults))));

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = Setting::get($key, $defaults[$key] ?? '');
        }

        foreach ([
            'show_phone_in_header', 'pwa_enabled', 'pwa_auto_mobile', 'app_banner_show_lead',
            'sms_enabled', 'sms_on_appointment', 'sms_on_confirm', 'sms_on_advocacy', 'sms_notify_admin',
        ] as $boolKey) {
            $data[$boolKey] = ($data[$boolKey] ?? '0') === '1';
        }

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
                        TextInput::make('social_instagram')->label('اینستاگرام'),
                        TextInput::make('social_linkedin')->label('لینکدین'),
                        TextInput::make('social_whatsapp')->label('واتساپ'),
                    ])->columns(3),
                ]),
                Tab::make('محدودیت نمایش')->schema([
                    Section::make('تعداد آیتم در صفحه اصلی / وب‌اپ')
                        ->description('۰ یعنی نمایش همه موارد فعال.')
                        ->schema([
                            TextInput::make('home_services_limit')->label('خدمات')->numeric()->minValue(0)->default(0),
                            TextInput::make('home_team_limit')->label('تیم حقوقی')->numeric()->minValue(0)->default(0),
                            TextInput::make('home_faq_limit')->label('سوالات متداول')->numeric()->minValue(0)->default(0),
                            TextInput::make('home_testimonials_limit')->label('نظرات')->numeric()->minValue(0)->default(0),
                            TextInput::make('home_posts_limit')->label('مقالات')->numeric()->minValue(0)->default(0),
                        ])->columns(2),
                ]),
                Tab::make('وب‌اپ / وب‌سرویس')->schema([
                    Section::make('فعال‌سازی وب‌اپ')->description('تنظیمات نصب و هدایت خودکار موبایل به وب‌اپ.')->schema([
                        Toggle::make('pwa_enabled')->label('فعال‌سازی وب‌اپ (PWA)')->inline(false),
                        Toggle::make('pwa_auto_mobile')->label('هدایت خودکار موبایل به /app')->inline(false),
                        TextInput::make('pwa_name')->label('نام اپ'),
                        TextInput::make('pwa_short_name')->label('نام کوتاه'),
                        Textarea::make('pwa_description')->label('توضیح اپ')->rows(2)->columnSpanFull(),
                        TextInput::make('pwa_theme_color')->label('رنگ تم')->placeholder('#0a1628'),
                        TextInput::make('pwa_bg_color')->label('رنگ اسپلش')->placeholder('#0a1628'),
                        TextInput::make('pwa_start_url')->label('آدرس شروع')->default('/app'),
                    ])->columns(2),
                    Section::make('سایز بنر وب‌اپ')->schema([
                        Select::make('app_banner_size')
                            ->label('سایز پیش‌فرض بنر')
                            ->options([
                                'compact' => 'فشرده (کوتاه)',
                                'medium' => 'متوسط (پیشنهادی)',
                                'large' => 'بزرگ',
                                'custom' => 'سفارشی (با درصد ارتفاع)',
                            ])
                            ->required()
                            ->live(),
                        TextInput::make('app_banner_height')
                            ->label('ارتفاع سفارشی (٪ صفحه)')
                            ->numeric()->minValue(25)->maxValue(70)->suffix('%')
                            ->visible(fn ($get): bool => $get('app_banner_size') === 'custom'),
                        Select::make('app_banner_position')
                            ->label('موقعیت تصویر بنر')
                            ->options([
                                'center top' => 'بالا',
                                'center 35%' => 'کمی بالاتر از وسط',
                                'center center' => 'وسط',
                                'center bottom' => 'پایین',
                            ])
                            ->required(),
                        Toggle::make('app_banner_show_lead')
                            ->label('نمایش متن توضیح زیر عنوان بنر')
                            ->inline(false),
                    ])->columns(2),
                ]),
                Tab::make('پیامک نیازپرداز')->schema([
                    Section::make('اتصال پنل پیامک')
                        ->description('اطلاعات ورود پنل https://niazpardaz-sms.com — ترجیحاً نام کاربری/رمز یا API Key.')
                        ->schema([
                            Toggle::make('sms_enabled')->label('فعال‌سازی ارسال پیامک')->inline(false),
                            Toggle::make('sms_on_appointment')->label('پیامک بعد از ثبت نوبت برای متقاضی')->inline(false),
                            Toggle::make('sms_on_confirm')->label('پیامک بعد از تأیید نوبت در ادمین')->inline(false),
                            Toggle::make('sms_on_advocacy')->label('پیامک بعد از تأیید وکالت موکل')->inline(false),
                            Toggle::make('sms_notify_admin')->label('اطلاع به مدیر هنگام نوبت جدید')->inline(false),
                            TextInput::make('sms_username')->label('نام کاربری پنل'),
                            TextInput::make('sms_password')->label('رمز عبور پنل')->password()->revealable(),
                            TextInput::make('sms_api_key')->label('API Key (اختیاری)')->password()->revealable(),
                            TextInput::make('sms_from')->label('شماره فرستنده')->placeholder('مثلاً 3000...'),
                            TextInput::make('sms_admin_phone')->label('موبایل مدیر برای اطلاع')->placeholder('09xxxxxxxxx'),
                        ])->columns(2),
                    Section::make('متن پیامک‌ها')
                        ->description('نوبت: {name} {phone} {date} {time} {topic} {status} {brand} · وکالت: {name} {subject} {case_type} {fee} {date} {national_code} {brand}')
                        ->schema([
                            Textarea::make('sms_tpl_appointment')->label('متن بعد از ثبت نوبت')->rows(4)->columnSpanFull(),
                            Textarea::make('sms_tpl_confirm')->label('متن تأیید روز مشاوره')->rows(4)->columnSpanFull(),
                            Textarea::make('sms_tpl_admin')->label('متن اطلاع به مدیر')->rows(3)->columnSpanFull(),
                            Textarea::make('sms_tpl_advocacy')->label('متن تأیید وکالت برای موکل')->rows(4)->columnSpanFull(),
                        ]),
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
                    Actions::make([
                        $this->getSaveFormAction(),
                        Action::make('testSms')
                            ->label('ارسال پیامک آزمایشی به مدیر')
                            ->color('gray')
                            ->action('sendTestSms'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ([
            'show_phone_in_header', 'pwa_enabled', 'pwa_auto_mobile', 'app_banner_show_lead',
            'sms_enabled', 'sms_on_appointment', 'sms_on_confirm', 'sms_on_advocacy', 'sms_notify_admin',
        ] as $boolKey) {
            $data[$boolKey] = ! empty($data[$boolKey]) ? '1' : '0';
        }

        $size = $data['app_banner_size'] ?? 'medium';
        $preset = match ($size) {
            'compact' => '34',
            'large' => '52',
            'custom' => (string) max(25, min(70, (int) ($data['app_banner_height'] ?? 42))),
            default => '42',
        };
        $data['app_banner_height'] = $preset;

        Setting::many($data);

        Notification::make()
            ->title('تنظیمات ذخیره شد')
            ->body('وب‌سرویس، محدودیت‌ها و پنل پیامک به‌روز شدند.')
            ->success()
            ->send();
    }

    public function sendTestSms(): void
    {
        $this->save();
        $phone = trim((string) Setting::get('sms_admin_phone', ''));
        if ($phone === '') {
            Notification::make()->title('موبایل مدیر را وارد کنید')->danger()->send();

            return;
        }

        $result = app(NiazpardazSms::class)->send(
            $phone,
            'پیامک آزمایشی از پنل مدیریت '.Setting::get('site_name', '')
        );

        if (! empty($result['ok'])) {
            Notification::make()->title('پیامک آزمایشی ارسال شد')->success()->send();
        } else {
            Notification::make()
                ->title('ارسال ناموفق')
                ->body($result['error'] ?? ($result['body'] ?? 'خطای ناشناخته'))
                ->danger()
                ->send();
        }
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('ذخیره همه تنظیمات')
            ->submit('save');
    }
}
