<?php

namespace App\Filament\Resources\WhatsappMessages;

use App\Filament\Resources\WhatsappMessages\Pages\ManageWhatsappMessages;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
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

class WhatsappMessageResource extends Resource
{
    protected static ?string $model = WhatsappMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    protected static ?string $navigationLabel = 'WhatsApp desk';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('to_phone')->label('To (WhatsApp)')->required(),
            TextInput::make('template')->maxLength(80),
            Textarea::make('body')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('to_phone')->label('To'),
                TextColumn::make('template'),
                TextColumn::make('body')->limit(60),
                TextColumn::make('sent_at')->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['direction'] = 'out';
                        $data['from_label'] = 'EK Operations';
                        $data['sent_at'] = now();

                        return $data;
                    })
                    ->successRedirectUrl(fn ($record) => WhatsApp::link($record->body, $record->to_phone)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open WA')
                    ->url(fn (WhatsappMessage $record) => WhatsApp::link($record->body, $record->to_phone))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWhatsappMessages::route('/')];
    }
}
