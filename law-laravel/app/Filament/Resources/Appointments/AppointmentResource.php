<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ManageAppointments;
use App\Models\Appointment;
use App\Support\Jalali;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
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

    protected static ?string $navigationLabel = 'نوبت‌ها و درخواست‌ها';

    protected static ?string $modelLabel = 'نوبت';

    protected static ?string $pluralModelLabel = 'نوبت‌ها و درخواست‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'ورودی‌ها و پیگیری';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->inbox()->count();

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
                TextInput::make('name')->label('نام متقاضی')->required(),
                TextInput::make('phone')->label('تلفن')->tel()->required(),
                TextInput::make('email')->label('ایمیل')->email(),
                TextInput::make('topic')->label('موضوع'),
                TextInput::make('preferred_date')
                    ->label('تاریخ پیشنهادی (شمسی)')
                    ->placeholder('1404/05/18')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::format($state, 'Y/m/d') : '')
                    ->dehydrateStateUsing(fn (?string $state): ?string => Jalali::toGregorianDate($state)),
                TextInput::make('preferred_time')->label('ساعت پیشنهادی'),
                Textarea::make('notes')->label('توضیح متقاضی')->columnSpanFull(),
                Textarea::make('admin_note')->label('یادداشت داخلی ادمین')->columnSpanFull(),
                Select::make('status')
                    ->label('وضعیت گردش کار')
                    ->options([
                        'pending' => 'درخواست جدید (داشبورد)',
                        'viewed' => 'باز شده / در حال بررسی',
                        'confirmed' => 'تأیید نوبت (ارسال پیامک)',
                        'archived' => 'بایگانی',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                    ])
                    ->required()
                    ->default('pending')
                    ->helperText('تأیید نوبت = پیامک به متقاضی · بایگانی = انتقال به آرشیو'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('نام'),
            TextEntry::make('phone')->label('تلفن'),
            TextEntry::make('email')->label('ایمیل'),
            TextEntry::make('topic')->label('موضوع'),
            TextEntry::make('preferred_date')
                ->label('تاریخ پیشنهادی')
                ->formatStateUsing(fn ($state): string => $state ? Jalali::format($state, 'Y/m/d') : '—'),
            TextEntry::make('preferred_time')->label('ساعت'),
            TextEntry::make('status')
                ->label('وضعیت')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'pending' => 'درخواست جدید',
                    'viewed' => 'باز شده',
                    'confirmed' => 'تأیید شده',
                    'archived' => 'بایگانی',
                    'done' => 'انجام شده',
                    'cancelled' => 'لغو شده',
                    default => $state ?? '—',
                }),
            TextEntry::make('notes')->label('توضیح متقاضی')->columnSpanFull(),
            TextEntry::make('admin_note')->label('یادداشت ادمین')->columnSpanFull(),
            TextEntry::make('created_at')
                ->label('زمان ثبت')
                ->formatStateUsing(fn ($state): string => Jalali::formatDateTime($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('phone')->label('تلفن')->searchable(),
                TextColumn::make('topic')->label('موضوع')->searchable(),
                TextColumn::make('preferred_date')
                    ->label('تاریخ نوبت')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::format($state, 'Y/m/d') : '—')
                    ->sortable(),
                TextColumn::make('preferred_time')->label('ساعت'),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'جدید',
                        'viewed' => 'باز شده',
                        'confirmed' => 'تأیید شده',
                        'archived' => 'بایگانی',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'danger',
                        'viewed' => 'warning',
                        'confirmed' => 'success',
                        'archived', 'done' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::formatDateTime($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'جدید',
                        'viewed' => 'باز شده',
                        'confirmed' => 'تأیید شده',
                        'archived' => 'بایگانی',
                        'done' => 'انجام شده',
                        'cancelled' => 'لغو شده',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده')
                    ->after(function (Appointment $record): void {
                        $record->markViewed();
                    }),
                EditAction::make()
                    ->label('ویرایش')
                    ->after(function (Appointment $record): void {
                        if ($record->status === 'confirmed') {
                            Notification::make()
                                ->title('نوبت تأیید شد')
                                ->body('در صورت فعال بودن پیامک، تأیید برای متقاضی ارسال می‌شود.')
                                ->success()
                                ->send();
                        }
                        if ($record->status === 'archived' && $record->archived_at === null) {
                            $record->forceFill(['archived_at' => now()])->saveQuietly();
                        }
                    }),
                Action::make('archive')
                    ->label('بایگانی')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record): bool => ! in_array($record->status, ['archived', 'cancelled'], true))
                    ->action(function (Appointment $record): void {
                        $record->archive();
                        Notification::make()->title('به بایگانی منتقل شد')->success()->send();
                    }),
                DeleteAction::make()->label('حذف'),
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
