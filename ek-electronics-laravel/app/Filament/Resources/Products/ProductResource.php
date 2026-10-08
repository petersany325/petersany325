<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')->relationship('category', 'name')->required()->searchable(),
            TextInput::make('sku')->required()->maxLength(80),
            TextInput::make('name')->required()->maxLength(180),
            TextInput::make('slug')->required()->maxLength(180),
            TextInput::make('grade')->maxLength(80),
            TextInput::make('price')->numeric()->required()->prefix('R'),
            TextInput::make('cost')->numeric()->prefix('R'),
            TextInput::make('stock')->numeric()->required(),
            TextInput::make('image_path')->maxLength(255)->helperText('e.g. assets/img/hdd.jpg'),
            Textarea::make('description')->columnSpanFull(),
            Toggle::make('is_active')->default(true),
            Toggle::make('is_featured')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->searchable(),
                TextColumn::make('name')->searchable()->limit(40),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('price')->money('ZAR'),
                TextColumn::make('stock'),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_featured')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProducts::route('/')];
    }
}
