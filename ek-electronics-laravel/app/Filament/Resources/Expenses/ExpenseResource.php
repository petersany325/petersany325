<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Resources\Expenses\Pages\ManageExpenses;
use App\Models\Expense;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('spent_on')->required()->default(now()),
            Select::make('category')->options([
                'rent' => 'Rent',
                'salaries' => 'Salaries',
                'courier' => 'Courier',
                'stock' => 'Stock purchase',
                'whatsapp' => 'WhatsApp / telecom',
                'utilities' => 'Utilities',
                'marketing' => 'Marketing',
                'other' => 'Other',
            ])->required()->native(false),
            TextInput::make('description')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('amount')->numeric()->required()->prefix('R'),
            TextInput::make('vendor')->maxLength(120),
            Select::make('order_id')->relationship('order', 'number')->searchable()->preload()->label('Linked shop order'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('spent_on', 'desc')
            ->columns([
                TextColumn::make('spent_on')->date()->sortable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('description')->limit(40)->searchable(),
                TextColumn::make('vendor')->toggleable(),
                TextColumn::make('amount')->money('ZAR')->sortable(),
                TextColumn::make('order.number')->label('Order')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('category')->options([
                    'rent' => 'Rent',
                    'salaries' => 'Salaries',
                    'courier' => 'Courier',
                    'stock' => 'Stock purchase',
                    'whatsapp' => 'WhatsApp / telecom',
                    'utilities' => 'Utilities',
                    'marketing' => 'Marketing',
                    'other' => 'Other',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExpenses::route('/')];
    }
}
