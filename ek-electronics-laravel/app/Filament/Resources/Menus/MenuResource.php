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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Menu manager';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'menu item';

    protected static ?string $pluralModelLabel = 'Menu manager';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('location')->options([
                'header' => 'Header',
                'footer' => 'Footer (general)',
                'footer_shop' => 'Footer — Shop',
                'footer_services' => 'Footer — Services',
                'footer_legal' => 'Footer — Legal',
            ])->required()->native(false),
            TextInput::make('label')->required()->maxLength(120),
            TextInput::make('url')->required()->maxLength(255)->helperText('Path or full URL, e.g. /shop or https://…'),
            Select::make('target')->options([
                '_self' => 'Same tab',
                '_blank' => 'New tab',
            ])->default('_self')->required()->native(false),
            TextInput::make('sort_order')->numeric()->default(0)->required(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('location')->badge()->sortable(),
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('url')->limit(40)->toggleable(),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('location')->options([
                    'header' => 'Header',
                    'footer' => 'Footer (general)',
                    'footer_shop' => 'Footer — Shop',
                    'footer_services' => 'Footer — Services',
                    'footer_legal' => 'Footer — Legal',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMenus::route('/')];
    }
}
