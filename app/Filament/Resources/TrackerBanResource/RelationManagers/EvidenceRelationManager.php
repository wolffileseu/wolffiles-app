<?php

namespace App\Filament\Resources\TrackerBanResource\RelationManagers;

use App\Models\Tracker\TrackerServer;
use Filament\Forms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->required()->default('screenshot')->live()
                ->options(['screenshot'=>'Screenshot','demo'=>'Demo','video'=>'Video','link'=>'Link']),

            // File upload for screenshot/demo (private S3, same pattern as DemoResource)
            Forms\Components\FileUpload::make('file_path')
                ->label('File (screenshot / demo)')
                ->disk('s3')->directory('ban-evidence')->visibility('private')
                ->maxSize(20480)
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['screenshot','demo']))
                ->acceptedFileTypes(fn (Forms\Get $get) => $get('type') === 'screenshot'
                    ? ['image/jpeg','image/png','image/webp','image/gif']
                    : null),

            // External URL for link/video
            Forms\Components\TextInput::make('external_url')->label('URL (video / link)')->url()->maxLength(512)
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['video','link'])),

            Forms\Components\TextInput::make('caption')->label('Caption')->maxLength(255)->columnSpanFull(),

            Forms\Components\Select::make('server_id')->label('Server')->searchable()
                ->options(fn () => TrackerServer::orderBy('hostname_clean')->limit(1000)->get()
                    ->mapWithKeys(fn ($s) => [$s->id => $s->hostname_clean ?: $s->hostname ?: "Server #{$s->id}"])->all()),

            Forms\Components\DateTimePicker::make('occurred_at')->label('When recorded'),

            Forms\Components\Toggle::make('is_public')->label('Public evidence')
                ->helperText('Only public evidence is shown on the public badge.'),

            Forms\Components\Hidden::make('uploaded_by')->default(fn () => auth()->id()),
            Forms\Components\Hidden::make('created_at')->default(fn () => now()),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('s3')->visibility('private')
                    ->visible(fn () => true)
                    ->getStateUsing(function ($record) {
                        if ($record->file_path && $record->type === 'screenshot') {
                            try { return Storage::disk('s3')->temporaryUrl($record->file_path, now()->addHour()); }
                            catch (\Throwable $e) { return null; }
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('caption')->limit(30)->placeholder('—'),
                Tables\Columns\IconColumn::make('is_public')->label('Public')->boolean(),
                Tables\Columns\TextColumn::make('server.hostname_clean')->label('Server')->limit(20)->placeholder('—'),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('By')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->url(60), true)
                    ->visible(fn ($record) => $record->file_path || $record->external_url),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
