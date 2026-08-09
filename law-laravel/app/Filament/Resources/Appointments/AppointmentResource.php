<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ManageAppointments;
use App\Models\Appointment;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'نوبت‌های رزرو شده';

    protected static ?string $modelLabel = 'نوبت';

    protected static ?string $pluralModelLabel = 'نوبت‌های رزرو شده';

    protected static string|UnitEnum|null $navigationGroup = 'ورودی‌ها و پیگیری';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->required(),
                TextInput::make('phone')
                    ->label('تلفن')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email(),
                TextInput::make('topic')
                    ->label('موضوع'),
                DatePicker::make('preferred_date')
                    ->label('تاریخ پیشنهادی'),
                TextInput::make('preferred_time')
                    ->label('ساعت پیشنهادی'),
                Textarea::make('notes')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'confirmed' => 'تأیید شده',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                    ])
                    ->required()
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable(),
                TextColumn::make('topic')
                    ->label('موضوع')
                    ->searchable(),
                TextColumn::make('preferred_date')
                    ->label('تاریخ')
                    ->date('Y/m/d')
                    ->sortable(),
                TextColumn::make('preferred_time')
                    ->label('ساعت'),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'در انتظار',
                        'confirmed' => 'تأیید شده',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'done' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('ثبت شده')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                TextColumn::make('email')
                    ->label('ایمیل')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('notes')
                    ->label('توضیح')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'confirmed' => 'تأیید شده',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAppointments::route('/'),
        ];
    }
}
