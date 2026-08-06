<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'پیشخوان';

    protected static ?string $title = 'پیشخوان مدیریت';

    protected static string|UnitEnum|null $navigationGroup = 'خانه کنترل پنل';

    protected static ?int $navigationSort = -10;
}
