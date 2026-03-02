<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\SocialMedia\SocialMediaService;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Content';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_posts');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->maxLength(255)
                ->hint('Leave empty to auto-generate')
                ->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('excerpt')->rows(3)->hint('Short preview text for listings'),
            Forms\Components\RichEditor::make('content')->required()->columnSpanFull(),
            Forms\Components\FileUpload::make('featured_image')
                ->disk('s3')
                ->directory('posts/images')
                ->image()
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('16:9')
                ->imageResizeTargetWidth('1200')
                ->imageResizeTargetHeight('675')
                ->label('Featured Image')
                ->hint('Recommended: 1200x675px. Shown on homepage and post detail.'),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Toggle::make('is_published')->default(false),
                Forms\Components\Toggle::make('is_pinned')->default(false),
                Forms\Components\DateTimePicker::make('published_at')->default(now()),
            ]),
            Forms\Components\KeyValue::make('title_translations')->label('Title Translations'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->disk('s3')
                    ->label('Image')
                    ->circular(false)
                    ->width(60)
                    ->height(40),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\IconColumn::make('is_pinned')->boolean(),
                Tables\Columns\TextColumn::make('view_count')->sortable(),
                Tables\Columns\TextColumn::make('published_at')->dateTime('d.m.Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
