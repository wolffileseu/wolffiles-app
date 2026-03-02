<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Community';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_comments');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('body')->required(),
            Forms\Components\Toggle::make('is_approved')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('body')
                    ->label('Comment')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('commentable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match(class_basename($state)) {
                        'File' => '📁 File',
                        'Post' => '📰 Post',
                        'LuaScript' => '📜 Lua',
                        default => class_basename($state),
                    })
                    ->size('sm'),
                Tables\Columns\TextColumn::make('commentable_title')
                    ->label('On')
                    ->getStateUsing(function (Comment $record) {
                        $commentable = $record->commentable;
                        if (!$commentable) return '(deleted)';
                        return $commentable->title ?? $commentable->name ?? '#' . $record->commentable_id;
                    })
                    ->url(function (Comment $record) {
                        $commentable = $record->commentable;
                        if (!$commentable) return null;
                        return match(class_basename($record->commentable_type)) {
                            'File' => route('files.show', $commentable),
                            'Post' => route('posts.show', $commentable),
                            default => null,
                        };
                    })
                    ->openUrlInNewTab()
                    ->color('warning')
                    ->limit(40)
                    ->size('sm'),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('✅')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('commentable_type')
                    ->label('Type')
                    ->options([
                        'App\Models\File' => '📁 File',
                        'App\Models\Post' => '📰 Post',
                        'App\Models\LuaScript' => '📜 Lua',
                    ]),
                Tables\Filters\Filter::make('not_approved')
                    ->label('Not Approved')
                    ->query(fn ($query) => $query->where('is_approved', false)),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('visit')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (Comment $record) {
                        $commentable = $record->commentable;
                        if (!$commentable) return null;
                        return match(class_basename($record->commentable_type)) {
                            'File' => route('files.show', $commentable),
                            'Post' => route('posts.show', $commentable),
                            default => null,
                        };
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Comment $record) => $record->commentable !== null),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Comment $record) => $record->update(['is_approved' => true]))
                    ->visible(fn (Comment $record) => !$record->is_approved),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('approve_all')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_approved' => true]))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
