<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Models\Menu;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    protected static ?string $navigationGroup = 'Content';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_menus');
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Select::make('location')
                ->options(['header' => 'Header', 'footer' => 'Footer', 'sidebar' => 'Sidebar'])
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\Repeater::make('allItems')
                ->relationship()
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent (for dropdown)')
                            ->options(function (Forms\Get $get) {
                                $menuId = $get('../../id');
                                if (!$menuId) return [];
                                return \App\Models\MenuItem::where('menu_id', $menuId)
                                    ->whereNull('parent_id')
                                    ->pluck('title', 'id');
                            })
                            ->placeholder('— Top Level —')
                            ->nullable(),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('page_select')
                            ->label('Link to Page')
                            ->options(Page::where('is_published', true)->pluck('title', 'slug'))
                            ->placeholder('— Or enter URL/Route below —')
                            ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                                // If URL matches a page slug, pre-select it
                                if ($record && $record->url && str_starts_with($record->url, '/page/')) {
                                    $slug = str_replace('/page/', '', $record->url);
                                    $component->state($slug);
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $set('url', '/page/' . $state);
                                    $set('route', null);
                                }
                            })
                            ->reactive()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->placeholder('/page/impressum or https://...')
                            ->helperText('Auto-filled when selecting a page'),
                    ]),
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('route')
                            ->label('Route name')
                            ->placeholder('e.g. files.index'),
                        Forms\Components\Select::make('target')
                            ->options(['_self' => 'Same Tab', '_blank' => 'New Tab'])
                            ->default('_self'),
                        Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ]),
                    Forms\Components\KeyValue::make('title_translations')
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
                Tables\Columns\TextColumn::make('name')->sortable(),
                Tables\Columns\TextColumn::make('location')->badge(),
                Tables\Columns\TextColumn::make('allItems_count')->counts('allItems')->label('Items'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}