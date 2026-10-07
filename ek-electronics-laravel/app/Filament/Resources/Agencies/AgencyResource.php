<?php

namespace App\Filament\Resources\Agencies;

use App\Filament\Resources\Agencies\Pages\ManageAgencies;
use App\Models\Agency;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AgencyResource extends Resource
{
    protected static ?string $model = Agency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Agents';

    protected static ?string $navigationLabel = 'Representations';

    protected static ?string $modelLabel = 'representation';

    protected static ?string $pluralModelLabel = 'Representations';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Brand we represent')->schema([
                TextInput::make('principal_name')->label('Brand')->required()->maxLength(120)->placeholder('SeDiv'),
                TextInput::make('territory')->required()->maxLength(120)->placeholder('South Africa'),
                TextInput::make('title')->label('Representation title')->required()->maxLength(180),
                TextInput::make('agent_name')->label('Local agent')->required()->default('EK Electronics')->maxLength(120),
                TextInput::make('slug')->maxLength(191)->helperText('Leave blank to build it from the brand and territory.'),
                TextInput::make('principal_url')->label('Brand website')->url()->maxLength(255),
                TextInput::make('logo_url')->label('Logo URL')->maxLength(255),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Toggle::make('is_featured')->label('Feature on the Agents page'),
                Toggle::make('is_active')->default(true),
            ])->columns(2),
            Section::make('Copy')->schema([
                Textarea::make('excerpt')->required()->rows(3)->columnSpanFull(),
                Textarea::make('body')->required()->rows(10)->columnSpanFull(),
                Textarea::make('coverage')->rows(6)->helperText('One line per service we provide under this agency.')->columnSpanFull(),
            ]),
            Section::make('Local contact')->schema([
                TextInput::make('website')->url()->maxLength(255),
                TextInput::make('email')->email()->maxLength(191),
                TextInput::make('phone')->maxLength(40),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('principal_name')->label('Brand')->searchable()->sortable(),
                TextColumn::make('territory')->searchable()->sortable(),
                TextColumn::make('title')->limit(40),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAgencies::route('/')];
    }
}
