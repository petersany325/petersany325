<?php

namespace App\Filament\Widgets;

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
            Stat::make('درخواست‌های جدید', Lead::query()->where('status', 'new')->count())
                ->description('فرم مشاوره سایت')
                ->color('warning'),
            Stat::make('نوبت‌های در انتظار', Appointment::query()->where('status', 'pending')->count())
                ->description('نیاز به پیگیری')
                ->color('danger'),
            Stat::make('مقالات منتشرشده', Post::query()->where('is_published', true)->count())
                ->description('وبلاگ حقوقی')
                ->color('success'),
            Stat::make('خدمات فعال', Service::query()->where('is_active', true)->count())
                ->description('حوزه‌های تخصصی')
                ->color('primary'),
        ];
    }
}
