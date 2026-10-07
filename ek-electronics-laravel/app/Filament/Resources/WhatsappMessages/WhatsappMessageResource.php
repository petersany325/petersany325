<?php

namespace App\Filament\Resources\WhatsappMessages;

use App\Filament\Resources\WhatsappMessages\Pages\ManageWhatsappMessages;
use App\Models\Setting;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
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
use Filament\Tables\Filters\SelectFilter;
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
            Select::make('template')->options([
                'manual' => 'Manual chat',
                'order' => 'Order',
                'ticket' => 'Ticket',
                'invoice' => 'Invoice',
                'recovery' => 'Recovery',
                'contact' => 'Contact',
            ])->default('manual')->native(false),
            Textarea::make('body')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('direction')->badge()
                    ->color(fn (?string $state): string => $state === 'in' ? 'info' : 'success'),
                TextColumn::make('to_phone')->label('Phone')->searchable(),
                TextColumn::make('from_label')->label('From / agent')->toggleable(),
                TextColumn::make('template')->badge(),
                TextColumn::make('body')->limit(60)->searchable(),
                TextColumn::make('sent_at')->dateTime()->since(),
            ])
            ->filters([
                SelectFilter::make('direction')->options(['in' => 'Inbound', 'out' => 'Outbound']),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New chat message')
                    ->mutateDataUsing(function (array $data): array {
                        $data['direction'] = 'out';
                        $data['from_label'] = WhatsApp::displayName();
                        $data['sent_at'] = now();

                        return $data;
                    })
                    ->after(function (WhatsappMessage $record): void {
                        if (WhatsApp::apiEnabled()) {
                            WhatsApp::sendViaApi($record->to_phone, $record->body);
                        }
                    })
                    ->successRedirectUrl(function (WhatsappMessage $record) {
                        if (WhatsApp::apiEnabled() && ! Setting::bool('whatsapp_api_fallback_wame', true)) {
                            return null;
                        }

                        return WhatsApp::link($record->body, $record->to_phone);
                    }),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open WA')
                    ->url(fn (WhatsappMessage $record) => WhatsApp::link($record->body, $record->to_phone))
                    ->openUrlInNewTab(),
                Action::make('api_send')
                    ->label('Send via API')
                    ->visible(fn () => WhatsApp::apiEnabled())
                    ->action(function (WhatsappMessage $record): void {
                        $ok = WhatsApp::sendViaApi($record->to_phone, $record->body);
                        \Filament\Notifications\Notification::make()
                            ->title($ok ? 'Sent via API' : 'API send failed')
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),
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
