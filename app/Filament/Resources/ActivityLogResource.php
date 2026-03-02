<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Activity Log';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_activity_log');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('System / Guest')
                    ->searchable()
                    ->size('sm'),
                Tables\Columns\BadgeColumn::make('action')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['upload', 'approve', 'register', 'wiki_submit', 'tutorial_submit']),
                        'danger' => fn ($state) => in_array($state, ['reject', 'delete', 'comment_delete', 'login_failed']),
                        'warning' => fn ($state) => in_array($state, ['rate', 'report', 'unfavorite']),
                        'info' => fn ($state) => in_array($state, ['download', 'comment', 'favorite', 'search', 'fastdl_download']),
                        'primary' => fn ($state) => in_array($state, ['login', 'logout', 'profile_update', 'settings_change']),
                        'gray' => fn ($state) => in_array($state, ['contact', 'poll_vote']),
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'login' => '🔑 Login',
                        'logout' => '🚪 Logout',
                        'register' => '🆕 Register',
                        'login_failed' => '❌ Login Failed',
                        'upload' => '📤 Upload',
                        'download' => '📥 Download',
                        'approve' => '✅ Approve',
                        'reject' => '❌ Reject',
                        'delete' => '🗑️ Delete',
                        'edit' => '✏️ Edit',
                        'comment' => '💬 Comment',
                        'comment_delete' => '💬🗑️ Comment Del',
                        'rate' => '⭐ Rate',
                        'favorite' => '❤️ Favorite',
                        'unfavorite' => '💔 Unfavorite',
                        'report' => '🚩 Report',
                        'search' => '🔍 Search',
                        'contact' => '📬 Contact',
                        'poll_vote' => '📊 Poll Vote',
                        'profile_update' => '👤 Profile',
                        'settings_change' => '⚙️ Settings',
                        'wiki_submit' => '📖 Wiki',
                        'tutorial_submit' => '📖 Tutorial',
                        'donation' => '💰 Donation',
                        'fastdl_download' => '🚀 FastDL',
                        'fastdl_upload' => '🚀 FastDL Up',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Details')
                    ->limit(80)
                    ->wrap()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Data')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $props = is_array($state) ? $state : json_decode($state, true);
                        if (!$props || !is_array($props)) return is_scalar($props) ? (string)$props : '-';
                        $parts = [];
                        foreach ($props as $k => $v) {
                            if (is_array($v)) $v = json_encode($v);
                            if (strlen($v) > 50) $v = substr($v, 0, 50) . '...';
                            $parts[] = "{$k}: {$v}";
                        }
                        return implode(' · ', $parts);
                    })
                    ->size('sm')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        // Auth
                        'login' => '🔑 Login',
                        'logout' => '🚪 Logout',
                        'register' => '🆕 Register',
                        'login_failed' => '❌ Login Failed',
                        // Files
                        'upload' => '📤 Upload',
                        'download' => '📥 Download',
                        'approve' => '✅ Approve',
                        'reject' => '❌ Reject',
                        'delete' => '🗑️ Delete',
                        'edit' => '✏️ Edit',
                        // Community
                        'comment' => '💬 Comment',
                        'comment_delete' => '💬🗑️ Comment Delete',
                        'rate' => '⭐ Rate',
                        'favorite' => '❤️ Favorite',
                        'unfavorite' => '💔 Unfavorite',
                        'report' => '🚩 Report',
                        // User Actions
                        'search' => '🔍 Search',
                        'contact' => '📬 Contact',
                        'poll_vote' => '📊 Poll Vote',
                        'profile_update' => '👤 Profile Update',
                        // Content
                        'wiki_submit' => '📖 Wiki Submit',
                        'tutorial_submit' => '📖 Tutorial Submit',
                        // Finance
                        'donation' => '💰 Donation',
                        // FastDL
                        'fastdl_download' => '🚀 FastDL Download',
                        'fastdl_upload' => '🚀 FastDL Upload',
                    ])
                    ->multiple()
                    ->label('Action'),
                Tables\Filters\Filter::make('has_user')
                    ->label('Logged-in Users Only')
                    ->query(fn ($query) => $query->whereNotNull('user_id')),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->startOfWeek())),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Activity Log #' . $record->id)
                    ->modalContent(fn ($record) => view('filament.modals.activity-log-detail', ['log' => $record]))
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('delete_selected')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->delete()),
            ])
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
