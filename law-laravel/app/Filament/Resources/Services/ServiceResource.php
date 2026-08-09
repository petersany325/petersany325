<?php

namespace App\Filament\Resources\Services;

use App\Support\Jalali;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;
    protected static ?string $navigationLabel = 'خدمات';

    protected static ?string $modelLabel = 'خدمات';

    protected static ?string $pluralModelLabel = 'خدمات';

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت محتوا';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => ManageServices::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }
}
