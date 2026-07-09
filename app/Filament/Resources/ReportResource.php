<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Models\File;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\ReportResource\Pages\ListReports;
use App\Filament\Resources\ReportResource\Pages\CreateReport;
use App\Filament\Resources\ReportResource\Pages\EditReport;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static string | \UnitEnum | null $navigationGroup = 'Moderation';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('reportable_type'),
            TextInput::make('reportable_id'),
            TextInput::make('reason'),
            Textarea::make('description'),
            Select::make('status')
                ->options(['pending' => 'Pending', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'])
                ->required(),
            Textarea::make('admin_notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('reportable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                TextColumn::make('reportable_id')->label('Item ID'),
                TextColumn::make('reason')->badge()
                    ->colors([
                        'danger' => 'copyright',
                        'warning' => 'spam',
                        'info' => 'broken',
                        'secondary' => 'other',
                    ]),
                TextColumn::make('user.name')->label('Reporter'),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'reviewed',
                        'success' => 'resolved',
                        'secondary' => 'dismissed',
                    ]),
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'])
                    ->default('pending'),
                SelectFilter::make('reason')
                    ->options(['copyright' => 'Copyright', 'broken' => 'Broken', 'spam' => 'Spam', 'inappropriate' => 'Inappropriate', 'other' => 'Other']),
            ])
            ->recordActions([
                EditAction::make(),

                // View reported content
                Action::make('view_content')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(function (Report $record) {
                        if ($record->reportable_type === 'App\\Models\\File') {
                            $file = File::find($record->reportable_id);
                            return $file ? route('files.show', $file) : null;
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),

                // Quick resolve
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Report $record) => $record->status === 'pending')
                    ->schema([
                        Textarea::make('admin_notes')->label('Notes'),
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
                Action::make('dismiss')
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
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('bulk_dismiss')
                    ->label('Dismiss Selected')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(fn ($r) => $r->update(['status' => 'dismissed', 'resolved_by' => auth()->id(), 'resolved_at' => now()]));
                        Notification::make()->title(count($records) . ' reports dismissed.')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }
}
