<?php

namespace App\Filament\Resources\Leads;

use App\Support\Jalali;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'پیام‌های مشاوره';

    protected static ?string $modelLabel = 'پیام مشاوره';

    protected static ?string $pluralModelLabel = 'پیام‌های مشاوره';

    protected static string|UnitEnum|null $navigationGroup = 'ورودی‌ها و پیگیری';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', 'new')->count();

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
                TextInput::make('name')
                    ->label('نام')
                    ->required(),
                TextInput::make('phone')
                    ->label('تلفن')
                    ->tel()
                    ->required(),
                TextInput::make('topic')
                    ->label('موضوع'),
                Textarea::make('message')
                    ->label('پیام')
                    ->columnSpanFull(),
                TextInput::make('ip')
                    ->label('IP')
                    ->disabled(),
                Select::make('status')
                    ->label('وضعیت')
                    ->options([
                        'new' => 'جدید',
                        'in_progress' => 'در حال پیگیری',
                        'done' => 'بسته شده',
                    ])
                    ->required()
                    ->default('new'),
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
                TextColumn::make('message')
                    ->label('پیام')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'new' => 'جدید',
                        'in_progress' => 'در حال پیگیری',
                        'done' => 'بسته شده',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'new' => 'warning',
                        'in_progress' => 'info',
                        'done' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('ثبت شده')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—')
                    ->sortable(),
                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'new' => 'جدید',
                        'in_progress' => 'در حال پیگیری',
                        'done' => 'بسته شده',
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
            'index' => ManageLeads::route('/'),
        ];
    }
}
