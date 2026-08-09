<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ManageAppointments;
use App\Models\Appointment;
use App\Support\Jalali;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $recordTitleAttribute = 'name';

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
                TextInput::make('preferred_date')
                    ->label('تاریخ پیشنهادی (شمسی)')
                    ->placeholder('1404/05/18')
                    ->helperText('فرمت: سال/ماه/روز شمسی')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::format($state, 'Y/m/d') : '')
                    ->dehydrateStateUsing(fn (?string $state): ?string => Jalali::toGregorianDate($state)),
                TextInput::make('preferred_time')
                    ->label('ساعت پیشنهادی')
                    ->placeholder('مثلاً ۱۰ صبح'),
                Textarea::make('notes')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'confirmed' => 'تأیید شده (ارسال پیامک)',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                    ])
                    ->required()
                    ->default('pending')
                    ->helperText('با انتخاب «تأیید شده» پیامک تأیید برای متقاضی ارسال می‌شود.'),
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
                    ->label('تاریخ نوبت')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::format($state, 'Y/m/d') : '—')
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
                    ->formatStateUsing(fn ($state): string => Jalali::formatDateTime($state))
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
                EditAction::make()
                    ->after(function (Appointment $record): void {
                        if ($record->status === 'confirmed') {
                            Notification::make()
                                ->title('وضعیت تأیید شد')
                                ->body('در صورت فعال بودن پنل پیامک، پیامک تأیید ارسال می‌شود.')
                                ->success()
                                ->send();
                        }
                    }),
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'topic', 'email'];
    }
}
