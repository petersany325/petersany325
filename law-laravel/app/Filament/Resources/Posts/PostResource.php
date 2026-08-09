<?php

namespace App\Filament\Resources\Posts;

use App\Support\Jalali;
use App\Filament\Resources\Posts\Pages\ManagePosts;
use App\Models\Post;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'مقالات';

    protected static ?string $modelLabel = 'مقالات';

    protected static ?string $pluralModelLabel = 'مقالات';

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت محتوا';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('excerpt'),
                Textarea::make('body')
                    ->columnSpanFull(),
                TextInput::make('cover_path'),
                Toggle::make('is_published')
                    ->label('منتشر شود')
                    ->default(true)
                    ->required(),
                TextInput::make('published_at')
                    ->label('تاریخ انتشار (شمسی)')
                    ->placeholder('1404/05/18 14:30')
                    ->helperText('فرمت: سال/ماه/روز و اختیاری ساعت')
                    ->default(fn (): string => Jalali::formatDateTime(now()))
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '')
                    ->dehydrateStateUsing(function (?string $state) {
                        if (! $state) {
                            return now();
                        }
                        $parts = preg_split('/\s+/', trim($state));
                        $date = Jalali::toGregorianDate($parts[0] ?? null);
                        if (! $date) {
                            return now();
                        }
                        $time = $parts[1] ?? '00:00';
                        if (! preg_match('/^\d{1,2}:\d{2}/', $time)) {
                            $time = '00:00';
                        }

                        return $date.' '.substr($time, 0, 5).':00';
                    }),
                TextInput::make('user_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('excerpt')
                    ->searchable(),
                TextColumn::make('cover_path')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—')
                    ->sortable(),
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->formatStateUsing(fn ($state): string => $state ? Jalali::formatDateTime($state) : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePosts::route('/'),
        ];
    }
}
