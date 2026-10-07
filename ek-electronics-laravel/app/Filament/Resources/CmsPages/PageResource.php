<?php

namespace App\Filament\Resources\CmsPages;

use App\Filament\Resources\CmsPages\Pages\ManagePages;
use App\Models\Page;
use BackedEnum;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page builder';

    protected static ?string $modelLabel = 'page';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')->schema([
                TextInput::make('title')->required()->maxLength(180)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        if (! filled($get('slug')) && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(191)->unique(ignoreRecord: true),
                Select::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])->default('published')->required()->native(false),
                Toggle::make('show_in_menu')->label('Show in header menu')->default(false),
                TextInput::make('meta_title')->maxLength(180),
                Textarea::make('meta_description')->rows(2)->columnSpanFull(),
            ])->columns(2),
            Section::make('Advanced page builder')->schema([
                Repeater::make('blocks')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->cloneable()
                    ->defaultItems(1)
                    ->schema([
                        Select::make('type')->options([
                            'hero' => 'Hero',
                            'heading' => 'Heading',
                            'text' => 'Text',
                            'image' => 'Image',
                            'cta' => 'Call to action',
                            'html' => 'HTML',
                            'faq' => 'FAQ item',
                            'products' => 'Featured products note',
                        ])->required()->native(false)->columnSpan(1),
                        Toggle::make('is_active')->default(true),
                        TextInput::make('heading')->maxLength(180)->columnSpanFull(),
                        Textarea::make('body')->rows(4)->columnSpanFull(),
                        TextInput::make('image_path')->maxLength(255)->helperText('e.g. assets/img/hdd.jpg'),
                        TextInput::make('button_label')->maxLength(80),
                        TextInput::make('button_url')->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('blocks_count')->counts('blocks')->label('Blocks'),
                IconColumn::make('show_in_menu')->boolean()->label('Menu'),
                TextColumn::make('updated_at')->dateTime()->since(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePages::route('/')];
    }
}
