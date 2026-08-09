<?php

namespace App\Filament\Resources\Menus;

use App\Filament\Resources\Menus\Pages\ManageMenus;
use App\Models\Menu;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'مدیریت منو';

    protected static ?string $modelLabel = 'آیتم منو';

    protected static ?string $pluralModelLabel = 'منوها';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم و حساب';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location')
                    ->label('محل نمایش')
                    ->options([
                        'header' => 'منوی بالای سایت',
                        'footer' => 'منوی فوتر',
                    ])
                    ->required()
                    ->default('header'),
                TextInput::make('label')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(120),
                TextInput::make('url')
                    ->label('لینک')
                    ->helperText('مثال: /blog یا #appointment یا https://...')
                    ->required()
                    ->maxLength(255),
                Select::make('style')
                    ->label('سبک نمایش')
                    ->options([
                        'regular' => 'معمولی',
                        'fancy' => 'فانتزی',
                        'cta' => 'دکمه اقدام (نوبت/مشاوره)',
                    ])
                    ->required()
                    ->default('regular'),
                TextInput::make('sort_order')
                    ->label('ترتیب')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
                Toggle::make('open_in_new_tab')
                    ->label('باز شدن در تب جدید')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('location')
                    ->label('محل')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'footer' ? 'فوتر' : 'هدر'),
                TextColumn::make('label')
                    ->label('عنوان')
                    ->searchable(),
                TextColumn::make('url')
                    ->label('لینک')
                    ->searchable(),
                TextColumn::make('style')
                    ->label('سبک')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fancy' => 'فانتزی',
                        'cta' => 'دکمه',
                        default => 'معمولی',
                    }),
                TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('location')
                    ->label('محل')
                    ->options([
                        'header' => 'هدر',
                        'footer' => 'فوتر',
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
            'index' => ManageMenus::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['label', 'url'];
    }
}
