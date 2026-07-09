<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\MenuItem;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\MenuResource\Pages\ListMenus;
use App\Filament\Resources\MenuResource\Pages\CreateMenu;
use App\Filament\Resources\MenuResource\Pages\EditMenu;
use App\Filament\Resources\MenuResource\Pages;
use App\Models\Menu;
use App\Models\Page;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bars-3';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';





    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            Select::make('location')
                ->options(['header' => 'Header', 'footer' => 'Footer', 'sidebar' => 'Sidebar'])
                ->required()
                ->unique(ignoreRecord: true),
            Repeater::make('allItems')
                ->relationship()
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')->required(),
                        Select::make('parent_id')
                            ->label('Parent (for dropdown)')
                            ->options(function (Get $get) {
                                $menuId = $get('../../id');
                                if (!$menuId) return [];
                                return MenuItem::where('menu_id', $menuId)
                                    ->whereNull('parent_id')
                                    ->pluck('title', 'id');
                            })
                            ->placeholder('— Top Level —')
                            ->nullable(),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('page_select')
                            ->label('Link to Page')
                            ->options(Page::where('is_published', true)->pluck('title', 'slug'))
                            ->placeholder('— Or enter URL/Route below —')
                            ->afterStateHydrated(function (Select $component, $record) {
                                // If URL matches a page slug, pre-select it
                                if ($record && $record->url && str_starts_with($record->url, '/page/')) {
                                    $slug = str_replace('/page/', '', $record->url);
                                    $component->state($slug);
                                }
                            })
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('url', '/page/' . $state);
                                    $set('route', null);
                                }
                            })
                            ->live()
                            ->dehydrated(false),
                        TextInput::make('url')
                            ->label('URL')
                            ->placeholder('/page/impressum or https://...')
                            ->helperText('Auto-filled when selecting a page'),
                    ]),
                    Grid::make(4)->schema([
                        TextInput::make('route')
                            ->label('Route name')
                            ->placeholder('e.g. files.index'),
                        Select::make('target')
                            ->options(['_self' => 'Same Tab', '_blank' => 'New Tab'])
                            ->default('_self'),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                    ]),
                    KeyValue::make('title_translations')
                        ->label('Translations (de, en)')
                        ->keyLabel('Language')
                        ->valueLabel('Title'),
                ])
                ->orderColumn('sort_order')
                ->collapsible()
                ->itemLabel(fn (array $state): ?string =>
                    ($state['title'] ?? 'Menu Item') .
                    (!empty($state['parent_id']) ? ' ↳' : '') .
                    (!empty($state['url']) ? ' → ' . $state['url'] : '') .
                    (!empty($state['route']) ? ' → ' . $state['route'] : '')
                )
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable(),
                TextColumn::make('location')->badge(),
                TextColumn::make('allItems_count')->counts('allItems')->label('Items'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}