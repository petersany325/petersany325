<?php

namespace App\Filament\Pages;

use App\Services\SiteMaintenance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaintainSite extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'کش و تعمیر سایت';

    protected static ?string $title = 'بروزرسانی کش و تعمیر سایت';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم و حساب';

    protected static ?int $navigationSort = 90;

    /** @var list<string> */
    public array $lastLog = [];

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بروزرسانی کش سایت')
                ->description('کش لاراول، ویوها، تنظیمات و نسخهٔ فایل‌های CSS/JS/وب‌اپ را تازه می‌کند تا تغییرات فوری دیده شود.')
                ->schema([
                    Html::make('<p style="margin:0 0 .75rem;color:#3d5470;font-size:.82rem;line-height:1.7">بعد از تغییرات قالب، منو، تنظیمات یا پیامک، این دکمه را بزنید. نسخهٔ asset بالا می‌رود و کش مرورگر دور زده می‌شود.</p>'),
                    Actions::make([
                        Action::make('refreshCache')
                            ->label('بروزرسانی کش سایت')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->color('primary')
                            ->requiresConfirmation()
                            ->modalHeading('بروزرسانی کش؟')
                            ->modalDescription('کش اپلیکیشن، مسیرها، ویوها و نسخهٔ استاتیک‌ها پاک/تازه می‌شود.')
                            ->action('refreshCache'),
                    ]),
                ]),
            Section::make('تعمیر سایت')
                ->description('پوشه‌های storage را بررسی می‌کند، لینک public/storage را می‌سازد، مهاجرت‌های معلق را اجرا و در پایان کش را تازه می‌کند.')
                ->schema([
                    Html::make('<p style="margin:0 0 .75rem;color:#3d5470;font-size:.82rem;line-height:1.7">اگر صفحه سفید، خطای نوشتن لاگ، یا آپلود خراب دیدید از این گزینه استفاده کنید.</p>'),
                    Actions::make([
                        Action::make('repairSite')
                            ->label('تعمیر سایت')
                            ->icon(Heroicon::OutlinedWrenchScrewdriver)
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('تعمیر سایت انجام شود؟')
                            ->modalDescription('پوشه‌ها، storage:link، migrate و پاک‌سازی کش اجرا می‌شود.')
                            ->action('repairSite'),
                    ]),
                ]),
            Section::make('نتیجه آخرین عملیات')
                ->schema([
                    Html::make(fn (): string => '<pre style="margin:0;white-space:pre-wrap;font-family:Vazirmatn,Tahoma,monospace;font-size:.78rem;line-height:1.65;color:#152536;background:#f5f8fc;border:1px solid #9bb1c7;padding:.75rem">'
                        .e($this->lastLog === [] ? 'هنوز عملیاتی اجرا نشده است.' : implode("\n", $this->lastLog))
                        .'</pre>'),
                ]),
        ]);
    }

    public function refreshCache(): void
    {
        $result = app(SiteMaintenance::class)->refreshCache();
        $this->lastLog = $result['lines'];

        $n = Notification::make()
            ->title($result['ok'] ? 'کش سایت بروزرسانی شد' : 'بروزرسانی با خطا')
            ->body(implode(' | ', array_slice($result['lines'], 0, 3)));
        $result['ok'] ? $n->success()->send() : $n->warning()->send();
    }

    public function repairSite(): void
    {
        $result = app(SiteMaintenance::class)->repairSite();
        $this->lastLog = $result['lines'];

        $n = Notification::make()
            ->title($result['ok'] ? 'تعمیر سایت انجام شد' : 'تعمیر با هشدار/خطا')
            ->body(implode(' | ', array_slice($result['lines'], 0, 3)));
        $result['ok'] ? $n->success()->send() : $n->warning()->send();
    }
}
