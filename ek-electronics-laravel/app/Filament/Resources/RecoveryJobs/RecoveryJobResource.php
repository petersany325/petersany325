<?php

namespace App\Filament\Resources\RecoveryJobs;

use App\Filament\Resources\RecoveryJobs\Pages\ManageRecoveryJobs;
use App\Models\RecoveryJob;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RecoveryJobResource extends Resource
{
    protected static ?string $model = RecoveryJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Recovery jobs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('number')->required(),
            TextInput::make('client_name')->required(),
            TextInput::make('client_phone'),
            TextInput::make('media'),
            TextInput::make('stage')->required(),
            TextInput::make('quote')->numeric()->prefix('R'),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable(),
                TextColumn::make('client_name')->searchable(),
                TextColumn::make('media'),
                TextColumn::make('stage')->badge(),
                TextColumn::make('quote')->money('ZAR'),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp update')
                    ->color('success')
                    ->url(fn (RecoveryJob $record) => WhatsApp::link("{$record->number} update for {$record->client_name}: {$record->stage}. Quote R ".number_format((float) $record->quote, 2).'.'))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRecoveryJobs::route('/')];
    }
}
