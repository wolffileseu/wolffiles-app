<?php

namespace App\Filament\Resources\FileResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\AttachAction;
use Filament\Actions\Action;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use App\Models\File;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RelatedBotsRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedBots';

    protected static ?string $title = 'Bot / Waypoint Files';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-cpu-chip';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (int) $ownerRecord->category_id === 10; // only on maps
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('relation_type')
                ->options([
                    'bot_files' => 'Bot files',
                    'waypoints' => 'Waypoints',
                    'goals'     => 'Goals',
                ])
                ->default('bot_files')
                ->required(),
            TextInput::make('confidence')
                ->numeric()->minValue(0)->maxValue(1)->step(0.01)
                ->default(1.0)
                ->disabled()->dehydrated(),
            Toggle::make('is_manual')
                ->default(true)
                ->disabled()->dehydrated()
                ->helperText('Manual links are never overwritten by the auto-backfill.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(),
                TextColumn::make('title')->limit(40)->searchable(),
                TextColumn::make('file_name')->limit(40)->searchable()->copyable(),
                TextColumn::make('pivot.relation_type')->label('Type')->badge(),
                TextColumn::make('pivot.confidence')
                    ->label('Conf.')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (float) $state >= 0.85 => 'success',
                        (float) $state >= 0.70 => 'warning',
                        default                => 'danger',
                    }),
                IconColumn::make('pivot.is_manual')
                    ->label('Manual')
                    ->boolean(),
                TextColumn::make('download_count')
                    ->label('DLs')->numeric()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('manual')
                    ->label('Manual only')
                    ->queries(
                        true: fn (Builder $q) => $q->wherePivot('is_manual', true),
                        false: fn (Builder $q) => $q->wherePivot('is_manual', false),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title', 'file_name'])
                    ->recordSelectOptionsQuery(fn (Builder $q) => $q->where('category_id', 12))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('relation_type')
                            ->options(['bot_files' => 'Bot files', 'waypoints' => 'Waypoints', 'goals' => 'Goals'])
                            ->default('bot_files')->required(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['confidence'] = 1.000;
                        $data['source']     = 'manual';
                        $data['is_manual']  = true;
                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (File $record) => route('filament.admin.resources.files.edit', ['record' => $record->slug]))
                    ->openUrlInNewTab(),
                Action::make('promote')
                    ->label('Mark verified')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (File $record) => ! (bool) $record->pivot->is_manual)
                    ->requiresConfirmation()
                    ->action(function (File $record) {
                        $this->getOwnerRecord()->relatedBots()->updateExistingPivot($record->id, [
                            'is_manual'  => true,
                            'confidence' => 1.000,
                            'source'     => 'manual',
                            'updated_at' => now(),
                        ]);
                    }),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('pivot_confidence', 'desc');
    }
}
