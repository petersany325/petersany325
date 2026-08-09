<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageAppointments extends ManageRecords
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'نوبت‌ها و درخواست‌ها';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('ثبت دستی نوبت'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('درخواست‌های جدید')
                ->badge(Appointment::query()->inbox()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->inbox()),
            'open' => Tab::make('باز شده / در جریان')
                ->badge(Appointment::query()->open()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->open()),
            'archived' => Tab::make('بایگانی نوبت‌ها')
                ->badge(Appointment::query()->archived()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->archived()),
            'all' => Tab::make('همه')
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    }
}
