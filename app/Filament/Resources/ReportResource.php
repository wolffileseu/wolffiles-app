<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Moderation';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_reports');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('reportable_type'),
            Forms\Components\TextInput::make('reportable_id'),
            Forms\Components\TextInput::make('reason'),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Select::make('status')
                ->options(['pending' => 'Pending', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'])
                ->required(),
            Forms\Components\Textarea::make('admin_notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                Tables\Columns\TextColumn::make('reportable_id')->label('Item ID'),
                Tables\Columns\TextColumn::make('reason')->badge()
                    ->colors([
                        'danger' => 'copyright',
                        'warning' => 'spam',
                        'info' => 'broken',
                        'secondary' => 'other',
                    ]),
                Tables\Columns\TextColumn::make('user.name')->label('Reporter'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'reviewed',
                        'success' => 'resolved',
                        'secondary' => 'dismissed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'])
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('reason')
                    ->options(['copyright' => 'Copyright', 'broken' => 'Broken', 'spam' => 'Spam', 'inappropriate' => 'Inappropriate', 'other' => 'Other']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // View reported content
                Tables\Actions\Action::make('view_content')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(function (Report $record) {
                        if ($record->reportable_type === 'App\\Models\\File') {
                            $file = \App\Models\File::find($record->reportable_id);
                            return $file ? route('files.show', $file) : null;
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),

                // Quick resolve
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Report $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Notes'),
                    ])
                    ->action(function (Report $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'admin_notes' => $data['admin_notes'] ?? null,
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                        Notification::make()->title('Report resolved.')->success()->send();
                    }),

                // Dismiss
                Tables\Actions\Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (Report $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Report $record) {
                        $record->update([
                            'status' => 'dismissed',
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                        Notification::make()->title('Report dismissed.')->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('bulk_dismiss')
                    ->label('Dismiss Selected')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each(fn ($r) => $r->update(['status' => 'dismissed', 'resolved_by' => auth()->id(), 'resolved_at' => now()]));
                        Notification::make()->title(count($records) . ' reports dismissed.')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
