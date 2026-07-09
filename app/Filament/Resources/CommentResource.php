<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Resources\CommentResource\Pages\ListComments;
use App\Filament\Resources\CommentResource\Pages\EditComment;
use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Community';


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->required(),
            Toggle::make('is_approved')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->size('sm'),
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->size('sm'),
                TextColumn::make('body')
                    ->label('Comment')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->size('sm'),
                TextColumn::make('commentable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match(class_basename($state)) {
                        'File' => '📁 File',
                        'Post' => '📰 Post',
                        'LuaScript' => '📜 Lua',
                        default => class_basename($state),
                    })
                    ->size('sm'),
                TextColumn::make('commentable_title')
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
                IconColumn::make('is_approved')
                    ->label('✅')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('commentable_type')
                    ->label('Type')
                    ->options([
                        'App\Models\File' => '📁 File',
                        'App\Models\Post' => '📰 Post',
                        'App\Models\LuaScript' => '📜 Lua',
                    ]),
                Filter::make('not_approved')
                    ->label('Not Approved')
                    ->query(fn ($query) => $query->where('is_approved', false)),
                Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->recordActions([
                Action::make('visit')
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
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Comment $record) => $record->update(['is_approved' => true]))
                    ->visible(fn (Comment $record) => !$record->is_approved),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('approve_all')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_approved' => true]))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
            'edit' => EditComment::route('/{record}/edit'),
        ];
    }
}
