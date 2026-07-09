<?php

namespace App\Filament\Resources\TrackerBanResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Throwable;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Models\Tracker\TrackerServer;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class EvidenceRelationManager extends RelationManager
{
    protected static string $relationship = 'evidence';
    protected static ?string $title = 'Evidence';
    protected static ?string $recordTitleAttribute = 'caption';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->required()->default('screenshot')->live()
                ->options(['screenshot'=>'Screenshot','demo'=>'Demo','video'=>'Video','link'=>'Link']),

            // File upload for screenshot/demo (private S3, same pattern as DemoResource)
            FileUpload::make('file_path')
                ->label('File (screenshot / demo)')
                ->disk('s3')->directory('ban-evidence')->visibility('private')
                ->maxSize(20480)
                ->visible(fn (Get $get) => in_array($get('type'), ['screenshot','demo']))
                ->acceptedFileTypes(fn (Get $get) => $get('type') === 'screenshot'
                    ? ['image/jpeg','image/png','image/webp','image/gif']
                    : null),

            // External URL for link/video
            TextInput::make('external_url')->label('URL (video / link)')->url()->maxLength(512)
                ->visible(fn (Get $get) => in_array($get('type'), ['video','link'])),

            TextInput::make('caption')->label('Caption')->maxLength(255)->columnSpanFull(),

            Select::make('server_id')->label('Server')->searchable()
                ->options(fn () => TrackerServer::orderBy('hostname_clean')->limit(1000)->get()
                    ->mapWithKeys(fn ($s) => [$s->id => $s->hostname_clean ?: $s->hostname ?: "Server #{$s->id}"])->all()),

            DateTimePicker::make('occurred_at')->label('When recorded'),

            Toggle::make('is_public')->label('Public evidence')
                ->helperText('Only public evidence is shown on the public badge.'),

            Hidden::make('uploaded_by')->default(fn () => auth()->id()),
            Hidden::make('created_at')->default(fn () => now()),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')->badge(),
                ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('s3')->visibility('private')
                    ->visible(fn () => true)
                    ->getStateUsing(function ($record) {
                        if ($record->file_path && $record->type === 'screenshot') {
                            try { return Storage::disk('s3')->temporaryUrl($record->file_path, now()->addHour()); }
                            catch (Throwable $e) { return null; }
                        }
                        return null;
                    }),
                TextColumn::make('caption')->limit(30)->placeholder('—'),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('server.hostname_clean')->label('Server')->limit(20)->placeholder('—'),
                TextColumn::make('uploadedBy.name')->label('By')->placeholder('—'),
                TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->url(60), true)
                    ->visible(fn ($record) => $record->file_path || $record->external_url),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
