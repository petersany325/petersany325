<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Invoice;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('number')->required(),
            TextInput::make('customer_name')->required(),
            TextInput::make('customer_phone'),
            TextInput::make('amount')->numeric()->prefix('R')->required(),
            TextInput::make('vat_amount')->numeric()->prefix('R'),
            Select::make('status')->options([
                'open' => 'Open',
                'paid' => 'Paid',
                'late' => 'Late',
                'void' => 'Void',
            ])->required(),
            DatePicker::make('due_date'),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable(),
                TextColumn::make('customer_name')->searchable(),
                TextColumn::make('amount')->money('ZAR'),
                TextColumn::make('vat_amount')->money('ZAR')->label('VAT'),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_date')->date(),
            ])
            ->recordActions([
                Action::make('remind')
                    ->label('WhatsApp')
                    ->color('success')
                    ->url(fn (Invoice $record) => WhatsApp::link("Invoice {$record->number} for {$record->customer_name}: R ".number_format((float) $record->amount, 2).'. Please settle and reply PAID.'))
                    ->openUrlInNewTab()
                    ->after(fn (Invoice $record) => $record->update(['whatsapp_sent_at' => now()])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageInvoices::route('/')];
    }
}
