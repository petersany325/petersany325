<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Order;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('number')->required(),
            TextInput::make('customer_name')->required(),
            TextInput::make('customer_phone'),
            TextInput::make('customer_email')->email(),
            TextInput::make('company'),
            TextInput::make('city'),
            Select::make('status')->options([
                'new' => 'New',
                'paid' => 'Paid',
                'dispatched' => 'Dispatched',
                'collected' => 'Collected',
                'cancelled' => 'Cancelled',
            ])->required(),
            TextInput::make('total')->numeric()->prefix('R'),
            Textarea::make('notes')->columnSpanFull(),
            Textarea::make('whatsapp_payload')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable(),
                TextColumn::make('customer_name')->searchable(),
                TextColumn::make('total')->money('ZAR'),
                TextColumn::make('status')->badge(),
                TextColumn::make('whatsapp_sent_at')->dateTime()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->color('success')
                    ->url(fn (Order $record) => WhatsApp::link($record->whatsapp_payload ?: "Order {$record->number} update for {$record->customer_name}."))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrders::route('/')];
    }
}
