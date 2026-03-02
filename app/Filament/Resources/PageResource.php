<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Pages';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_pages');
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, ?Page $record) {
                    if (!$record) {
                        $set('slug', \Illuminate\Support\Str::slug($state));
                    }
                }),

            Forms\Components\TextInput::make('slug')
                ->maxLength(255)
                ->hint('Leave empty to auto-generate')
                ->unique(ignoreRecord: true),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'page' => 'Page',
                        'legal' => 'Legal (Impressum, Privacy)',
                        'info' => 'Info',
                    ])
                    ->default('page'),

                Forms\Components\Select::make('content_type')
                    ->label('Content Type')
                    ->options([
                        'richtext' => 'Rich Text Editor',
                        'html' => 'HTML Code',
                        'markdown' => 'Markdown',
                    ])
                    ->default('richtext')
                    ->live()
                    ->afterStateUpdated(fn ($state) => $state),
            ]),

            // Rich Text Editor (default)
            Forms\Components\RichEditor::make('content')
                ->columnSpanFull()
                ->visible(fn (callable $get) => ($get('content_type') ?? 'richtext') === 'richtext'),

            // HTML Code Editor
            Forms\Components\Textarea::make('content')
                ->label('HTML Content')
                ->columnSpanFull()
                ->rows(20)
                ->extraAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                ->visible(fn (callable $get) => $get('content_type') === 'html'),

            // Markdown Editor
            Forms\Components\MarkdownEditor::make('content')
                ->columnSpanFull()
                ->visible(fn (callable $get) => $get('content_type') === 'markdown'),

            Forms\Components\Select::make('template')
                ->options([
                    'default' => 'Default',
                    'full-width' => 'Full Width',
                    'sidebar' => 'With Sidebar',
                ])
                ->default('default'),

            Forms\Components\FileUpload::make('pdf_path')
                ->disk('s3')
                ->directory('pages/pdf')
                ->label('PDF Attachment')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(20480)
                ->hint('Upload a PDF file (max 20MB). Will be shown as download link on the page.'),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Toggle::make('is_published')->default(false),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ]),

            Forms\Components\Section::make('Translations')->schema([
                Forms\Components\KeyValue::make('title_translations')
                    ->label('Title Translations')
                    ->keyLabel('Language')
                    ->valueLabel('Title')
                    ->hint('e.g. de → Impressum, en → Imprint'),
                Forms\Components\KeyValue::make('content_translations')
                    ->label('Content Translations')
                    ->keyLabel('Language')
                    ->valueLabel('Content'),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable()->color('gray'),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'legal' => 'danger',
                    'info' => 'info',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('content_type')
                    ->label('Format')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'html' => 'warning',
                        'markdown' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'richtext')),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
