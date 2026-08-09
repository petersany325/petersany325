<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Post;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('نوبت‌های در انتظار', Appointment::query()->where('status', 'pending')->count())
                ->description('از فرم «درخواست نوبت» — برای مشاهده کلیک کنید')
                ->color('danger')
                ->url(AppointmentResource::getUrl()),
            Stat::make('پیام‌های مشاوره جدید', Lead::query()->where('status', 'new')->count())
                ->description('از فرم تماس پایین صفحه')
                ->color('warning')
                ->url(LeadResource::getUrl()),
            Stat::make('مقالات منتشرشده', Post::query()->where('is_published', true)->count())
                ->description('وبلاگ حقوقی')
                ->color('success'),
            Stat::make('خدمات فعال', Service::query()->where('is_active', true)->count())
                ->description('حوزه‌های تخصصی')
                ->color('primary'),
        ];
    }
}
