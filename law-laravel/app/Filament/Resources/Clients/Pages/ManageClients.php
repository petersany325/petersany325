<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageClients extends ManageRecords
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'پذیرش موکل و پرونده‌های وکالت';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('ثبت موکل / وکالت جدید'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'draft' => Tab::make('در انتظار بررسی')
                ->badge(Client::query()->where('status', 'draft')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'confirmed' => Tab::make('تأیید شده')
                ->badge(Client::query()->where('status', 'confirmed')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),
            'active' => Tab::make('در جریان')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'closed' => Tab::make('مختومه / بایگانی')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed')),
            'all' => Tab::make('همه')
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    }
}
