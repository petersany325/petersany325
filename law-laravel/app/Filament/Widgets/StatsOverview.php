<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Appointment;
use App\Models\Client;
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
            Stat::make('درخواست‌های نوبت جدید', Appointment::query()->inbox()->count())
                ->description('در داشبورد — مشاهده و سپس بایگانی')
                ->color('danger')
                ->url(AppointmentResource::getUrl(parameters: ['activeTab' => 'pending'])),
            Stat::make('نوبت‌های باز / در جریان', Appointment::query()->open()->count())
                ->description('مشاهده‌شده یا تأییدشده')
                ->color('warning')
                ->url(AppointmentResource::getUrl(parameters: ['activeTab' => 'open'])),
            Stat::make('موکلین در انتظار تأیید', Client::query()->where('status', 'draft')->count())
                ->description('پذیرش وکالت — فرم استاندارد موکل')
                ->color('info')
                ->url(ClientResource::getUrl(parameters: ['activeTab' => 'draft'])),
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
