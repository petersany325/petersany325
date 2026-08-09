<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\ManageClients;
use App\Models\Client;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $recordTitleAttribute = 'last_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'مراجعین و پرونده‌ها';

    protected static ?string $navigationLabel = 'موکلین (وکالت)';

    protected static ?string $modelLabel = 'موکل';

    protected static ?string $pluralModelLabel = 'موکلین';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', 'draft')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات موکل')
                    ->description('طبق استاندارد قرارداد الکترونیک وکالت: نام، نام خانوادگی، نام پدر، کد ملی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->label('نام')->required()->maxLength(100),
                        TextInput::make('last_name')->label('نام خانوادگی')->required()->maxLength(100),
                        TextInput::make('father_name')->label('نام پدر')->maxLength(100),
                        TextInput::make('national_code')
                            ->label('کد ملی / شماره ملی')
                            ->required()
                            ->length(10)
                            ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                if (! Client::isValidNationalCode((string) $value)) {
                                    $fail('کد ملی وارد شده معتبر نیست.');
                                }
                            }),
                        TextInput::make('id_number')->label('شماره شناسنامه')->maxLength(30),
                        TextInput::make('phone')->label('تلفن همراه (پیامک)')->tel()->required()->maxLength(20)
                            ->helperText('پیامک تأیید وکالت به این شماره ارسال می‌شود.'),
                        TextInput::make('mobile')->label('تلفن جایگزین')->tel()->maxLength(20),
                        TextInput::make('email')->label('ایمیل')->email()->maxLength(150),
                        Textarea::make('address')->label('نشانی')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('موضوع وکالت و پرونده')
                    ->description('موضوع وکالت‌نامه، طرف دعوا، نوع پرونده و شرح خواسته')
                    ->columns(2)
                    ->schema([
                        Select::make('case_type')
                            ->label('نوع پرونده / وکالت')
                            ->options([
                                'حقوقی' => 'حقوقی',
                                'کیفری' => 'کیفری',
                                'خانواده' => 'خانواده',
                                'تجاری / شرکتی' => 'تجاری / شرکتی',
                                'ثبتی / ملکی' => 'ثبتی / ملکی',
                                'کار / تأمین اجتماعی' => 'کار / تأمین اجتماعی',
                                'اداری / دیوان' => 'اداری / دیوان',
                                'سایر' => 'سایر',
                            ])
                            ->searchable(),
                        TextInput::make('subject')->label('موضوع وکالت')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('opponent')->label('طرف دعوا / طرف مقابل')->maxLength(255),
                        TextInput::make('referrer')->label('معرف')->maxLength(255),
                        Textarea::make('description')
                            ->label('توضیحات / شرح اظهارات موکل')
                            ->rows(4)
                            ->columnSpanFull(),
                        DatePicker::make('contract_date')
                            ->label('تاریخ قرارداد / وکالتنامه')
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                        TextInput::make('contract_no')->label('شماره قرارداد / وکالتنامه')->maxLength(100),
                        Select::make('status')
                            ->label('وضعیت وکالت')
                            ->options([
                                'draft' => 'پیش‌نویس / در انتظار بررسی',
                                'confirmed' => 'تأیید وکالت (ارسال پیامک به موکل)',
                                'active' => 'در جریان',
                                'closed' => 'مختومه',
                            ])
                            ->required()
                            ->default('draft')
                            ->helperText('با انتخاب «تأیید وکالت» در صورت فعال بودن پیامک، متن تأیید برای موکل ارسال می‌شود.'),
                    ]),
                Section::make('حق‌الوکاله و مبالغ')
                    ->columns(2)
                    ->schema([
                        TextInput::make('fee_agreed')
                            ->label('مبلغ توافقی حق‌الوکاله (ریال)')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('fee_paid')
                            ->label('مبلغ طی‌شده / پرداخت‌شده (ریال)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('مبلغی که موکل تاکنون بابت وکالت پرداخت کرده است.'),
                        Select::make('fee_method')
                            ->label('نحوه پرداخت')
                            ->options([
                                'نقدی' => 'نقدی',
                                'کارت به کارت' => 'کارت به کارت',
                                'چک' => 'چک',
                                'اقساطی' => 'اقساطی',
                                'طبق تعرفه' => 'طبق تعرفه',
                                'سایر' => 'سایر',
                            ]),
                        Textarea::make('admin_note')
                            ->label('یادداشت داخلی دفتر')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات موکل')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')->label('نام کامل'),
                        TextEntry::make('father_name')->label('نام پدر')->placeholder('—'),
                        TextEntry::make('national_code')->label('کد ملی')->copyable(),
                        TextEntry::make('id_number')->label('شماره شناسنامه')->placeholder('—'),
                        TextEntry::make('phone')->label('موبایل')->copyable(),
                        TextEntry::make('mobile')->label('تلفن جایگزین')->placeholder('—'),
                        TextEntry::make('email')->label('ایمیل')->placeholder('—'),
                        TextEntry::make('address')->label('نشانی')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('وکالت و پرونده')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('case_type')->label('نوع پرونده')->placeholder('—'),
                        TextEntry::make('subject')->label('موضوع وکالت')->columnSpanFull(),
                        TextEntry::make('description')->label('توضیحات')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('opponent')->label('طرف دعوا')->placeholder('—'),
                        TextEntry::make('referrer')->label('معرف')->placeholder('—'),
                        TextEntry::make('contract_date')
                            ->label('تاریخ قرارداد')
                            ->formatStateUsing(fn ($state): string => $state ? Jalali::format($state, 'Y/m/d') : '—'),
                        TextEntry::make('contract_no')->label('شماره قرارداد')->placeholder('—'),
                        TextEntry::make('status')->label('وضعیت')->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'draft' => 'پیش‌نویس',
                                'confirmed' => 'تأیید شده',
                                'active' => 'در جریان',
                                'closed' => 'مختومه',
                                default => (string) $state,
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'draft' => 'warning',
                                'confirmed' => 'success',
                                'active' => 'info',
                                'closed' => 'gray',
                                default => 'gray',
                            }),
                    ]),
                Section::make('مبالغ')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('fee_agreed')
                            ->label('حق‌الوکاله توافقی')
                            ->formatStateUsing(fn ($state): string => $state !== null ? number_format((int) $state).' ریال' : '—'),
                        TextEntry::make('fee_paid')
                            ->label('مبلغ طی‌شده')
                            ->formatStateUsing(fn ($state): string => $state !== null ? number_format((int) $state).' ریال' : '—'),
                        TextEntry::make('fee_remaining')
                            ->label('مانده')
                            ->formatStateUsing(fn ($state): string => number_format((int) $state).' ریال'),
                        TextEntry::make('fee_method')->label('نحوه پرداخت')->placeholder('—'),
                        TextEntry::make('admin_note')->label('یادداشت دفتر')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('confirmed_at')
                            ->label('زمان تأیید')
                            ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—'),
                        TextEntry::make('created_at')
                            ->label('ثبت')
                            ->formatStateUsing(fn ($state): string => Jalali::formatDateTime($state)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('full_name')->label('موکل')->searchable(['first_name', 'last_name'])->sortable(),
                TextColumn::make('national_code')->label('کد ملی')->searchable()->toggleable(),
                TextColumn::make('phone')->label('موبایل')->searchable(),
                TextColumn::make('subject')->label('موضوع وکالت')->limit(36)->searchable()->wrap(),
                TextColumn::make('case_type')->label('نوع')->toggleable(),
                TextColumn::make('fee_paid')
                    ->label('مبلغ طی‌شده')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((int) $state) : '—')
                    ->toggleable(),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'draft' => 'پیش‌نویس',
                        'confirmed' => 'تأیید شده',
                        'active' => 'در جریان',
                        'closed' => 'مختومه',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'warning',
                        'confirmed' => 'success',
                        'active' => 'info',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::formatDateTime($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options([
                    'draft' => 'پیش‌نویس',
                    'confirmed' => 'تأیید شده',
                    'active' => 'در جریان',
                    'closed' => 'مختومه',
                ]),
                SelectFilter::make('case_type')->label('نوع پرونده')->options([
                    'حقوقی' => 'حقوقی',
                    'کیفری' => 'کیفری',
                    'خانواده' => 'خانواده',
                    'تجاری / شرکتی' => 'تجاری / شرکتی',
                    'ثبتی / ملکی' => 'ثبتی / ملکی',
                    'کار / تأمین اجتماعی' => 'کار / تأمین اجتماعی',
                    'اداری / دیوان' => 'اداری / دیوان',
                    'سایر' => 'سایر',
                ]),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
                EditAction::make()
                    ->label('ویرایش')
                    ->after(function (Client $record): void {
                        if ($record->status === 'confirmed') {
                            Notification::make()
                                ->title('وکالت تأیید شد')
                                ->body('در صورت فعال بودن پیامک، تأیید وکالت برای موکل ارسال می‌شود.')
                                ->success()
                                ->send();
                        }
                    }),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClients::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'national_code', 'phone', 'subject'];
    }
}
