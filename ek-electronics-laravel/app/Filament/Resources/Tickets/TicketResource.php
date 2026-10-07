<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\ManageTickets;
use App\Models\Ticket;
use App\Services\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')->schema([
                TextInput::make('number')->default(fn () => 'TCK-'.now()->format('ymd').'-'.Str::upper(Str::random(4)))->required(),
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('email')->email()->required()->maxLength(191),
                TextInput::make('phone')->maxLength(40),
                TextInput::make('subject')->required()->maxLength(180)->columnSpanFull(),
                Select::make('department')->options([
                    'support' => 'Support',
                    'sales' => 'Sales',
                    'recovery' => 'Data recovery',
                    'accounts' => 'Accounts',
                ])->required()->native(false),
                Select::make('priority')->options([
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ])->default('normal')->required()->native(false),
                Select::make('status')->options([
                    'open' => 'Open',
                    'pending' => 'Pending',
                    'answered' => 'Answered',
                    'closed' => 'Closed',
                ])->default('open')->required()->native(false),
            ])->columns(2),
            Section::make('Replies')->schema([
                Repeater::make('replies')
                    ->relationship()
                    ->schema([
                        TextInput::make('author_name')->maxLength(120),
                        Toggle::make('is_staff')->default(true),
                        Textarea::make('body')->required()->rows(3)->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('subject')->searchable()->limit(40),
                TextColumn::make('department')->badge(),
                TextColumn::make('priority')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'primary',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'closed' => 'gray',
                        'answered' => 'success',
                        'pending' => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('last_reply_at')->dateTime()->since()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'pending' => 'Pending',
                    'answered' => 'Answered',
                    'closed' => 'Closed',
                ]),
                SelectFilter::make('department')->options([
                    'support' => 'Support',
                    'sales' => 'Sales',
                    'recovery' => 'Data recovery',
                    'accounts' => 'Accounts',
                ]),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->color('success')
                    ->url(fn (Ticket $record) => WhatsApp::link(
                        "Ticket {$record->number}: {$record->subject}",
                        $record->phone
                    ))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTickets::route('/')];
    }
}
