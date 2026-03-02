<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages;
use App\Models\File;
use App\Models\Tag;
use App\Services\DiscordWebhookService;
use App\Services\SocialMedia\SocialMediaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class FileResource extends Resource
{
    protected static ?string $model = File::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationGroup = 'Files';
    protected static ?int $navigationSort = 1;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_files');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('File')->tabs([

                // Tab 1: Content
                Forms\Components\Tabs\Tab::make('Content')->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(255),
                    Forms\Components\TextInput::make('slug')->maxLength(255)->unique(ignoreRecord: true),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required(),
                    Forms\Components\Textarea::make('description')->rows(4),
                    Forms\Components\RichEditor::make('description_html')->label('Description (Rich)'),
                ])->columns(2),

                // Tab 2: File Info
                Forms\Components\Tabs\Tab::make('File Info')->schema([
                    Forms\Components\TextInput::make('file_name'),
                    Forms\Components\TextInput::make('file_size')->numeric(),
                    Forms\Components\TextInput::make('file_hash')->label('SHA256'),
                    Forms\Components\TextInput::make('map_name'),
                    Forms\Components\TextInput::make('version'),
                    Forms\Components\TextInput::make('original_author'),
                    Forms\Components\Select::make('game')
                        ->options([
                            'ET' => 'Enemy Territory',
                            'RtCW' => 'Return to Castle Wolfenstein',
                            'ET Quake Wars' => 'ET: Quake Wars',
                            'ET-Domination' => 'ET-Domination',
                            'ETFortress' => 'ETFortress',
                            'True Combat Elite' => 'True Combat Elite',
                            'Wolf Classic' => 'Wolf Classic',
                            'Wolfenstein' => 'Wolfenstein',
                        ])
                        ->default('ET'),
                ])->columns(2),

                // Tab 3: Tags
                Forms\Components\Tabs\Tab::make('Tags')->schema([
                    Forms\Components\Select::make('tags')
                        ->label('Tags')
                        ->multiple()
                        ->relationship('tags', 'name')
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(50)
                                ->unique('tags', 'name'),
                        ])
                        ->helperText('Select existing tags or create new ones. Use tags like Objective, Frag, Trickjump, Sniper, etc.'),

                    Forms\Components\Placeholder::make('suggested_tags')
                        ->label('Suggested Tags')
                        ->content('Map Type: Objective, Frag, Trickjump, Deathmatch, CTF, Last Man Standing — Style: Sniper, Panzer, Rifle, CQB, Indoor, Outdoor — Size: Small, Medium, Large — Theme: WW2, Desert, Snow, Urban, Forest, Beach, Night — Quality: Final, Beta, Competitive, Fun Map'),
                ]),

                // Tab 4: Screenshots
                Forms\Components\Tabs\Tab::make('Screenshots')->schema([
                    Forms\Components\Placeholder::make('current_screenshots')
                        ->label('Current Screenshots')
                        ->content(function (?File $record): HtmlString {
                            if (!$record || $record->screenshots->isEmpty()) {
                                return new HtmlString('<p class="text-gray-500">No screenshots yet.</p>');
                            }

                            $html = '<div style="display: flex; flex-wrap: wrap; gap: 12px;">';
                            foreach ($record->screenshots as $screenshot) {
                                try {
                                    $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($screenshot->path, now()->addHour());
                                    $html .= '<div style="position: relative;">';
                                    $html .= '<img src="' . e($url) . '" style="width: 160px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #374151;">';
                                    $html .= '<span style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px;">#' . $screenshot->id . '</span>';
                                    $html .= '</div>';
                                } catch (\Exception $e) {
                                    $html .= '<div style="width: 160px; height: 100px; background: #374151; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 12px;">Error</div>';
                                }
                            }
                            $html .= '</div>';
                            return new HtmlString($html);
                        })
                        ->visible(fn (?File $record) => $record !== null),

                    Forms\Components\FileUpload::make('new_screenshots')
                        ->label('Add Screenshots')
                        ->helperText('Upload additional screenshots. Existing screenshots will be kept.')
                        ->multiple()
                        ->image()
                        ->maxSize(10240)
                        ->maxFiles(10)
                        ->disk('s3')
                        ->directory('screenshots/temp')
                        ->visibility('public')
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('delete_screenshot_ids')
                        ->label('Delete Screenshots (IDs)')
                        ->helperText('Enter screenshot IDs to delete, separated by commas (e.g. "12,15,18"). See IDs on images above.')
                        ->placeholder('e.g. 12,15,18')
                        ->dehydrated(false),
                ]),

                // Tab 5: Status
                Forms\Components\Tabs\Tab::make('Status')->schema([
                    Forms\Components\Select::make('status')
                        ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                        ->required(),
                    Forms\Components\Textarea::make('rejection_reason'),
                    Forms\Components\Toggle::make('is_featured'),
                    Forms\Components\TextInput::make('featured_label')->maxLength(50),
                    Forms\Components\Toggle::make('virus_clean'),
                    Forms\Components\TextInput::make('virus_scan_result'),
                ])->columns(2),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('user.name')->label('Uploader')->sortable(),
                Tables\Columns\TextColumn::make('category.name')->sortable(),
                Tables\Columns\TextColumn::make('game')->badge()->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->color('warning')
                    ->separator(', ')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('file_size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 1) . ' MB' : '-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('virus_clean')->boolean(),
                Tables\Columns\TextColumn::make('download_count')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('game')
                    ->options([
                        'ET' => 'Enemy Territory',
                        'RtCW' => 'RtCW',
                        'ET Quake Wars' => 'ET Quake Wars',
                    ]),
                Tables\Filters\SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Tags'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
        /** @var \App\Models\File $record */
                    ->visible(fn (File $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (File $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'published_at' => $record->published_at ?? now(),
                        ]);
                        DiscordWebhookService::notifyFileApproved($record);
                        app(SocialMediaService::class)->broadcastFileApproved($record);
                        Notification::make()->title('File approved!')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
        /** @var \App\Models\File $record */
                    ->visible(fn (File $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (File $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('File rejected.')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),

                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
        /** @var \App\Models\File $record */
                            if ($record->status === 'pending') {
                                $record->update([
                                    'status' => 'approved',
                                    'reviewed_by' => auth()->id(),
                                    'reviewed_at' => now(),
                                    'published_at' => $record->published_at ?? now(),
                                ]);
                                DiscordWebhookService::notifyFileApproved($record);
                                app(SocialMediaService::class)->broadcastFileApproved($record);
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} files approved!")->success()->send();
                    }),

                Tables\Actions\BulkAction::make('bulk_reject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason (applies to all)')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
        /** @var \App\Models\File $record */
                            if ($record->status === 'pending') {
                                $record->update([
                                    'status' => 'rejected',
                                    'rejection_reason' => $data['rejection_reason'],
                                    'reviewed_by' => auth()->id(),
                                    'reviewed_at' => now(),
                                ]);
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} files rejected.")->warning()->send();
                    }),

                Tables\Actions\BulkAction::make('bulk_tag')
                    ->label('Add Tags')
                    ->icon('heroicon-o-tag')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('tags')
                            ->label('Tags to add')
                            ->multiple()
                            ->options(Tag::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(50),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $tag = Tag::firstOrCreate(
                                    ['slug' => \Illuminate\Support\Str::slug($data['name'])],
                                    ['name' => $data['name']]
                                );
                                return $tag->id;
                            }),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        if (!empty($data['tags'])) {
                            foreach ($records as $record) {
        /** @var \App\Models\File $record */
                                $record->tags()->syncWithoutDetaching($data['tags']);
                            }
                            Notification::make()->title('Tags added to ' . $records->count() . ' files!')->success()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFiles::route('/'),
            'create' => Pages\CreateFile::route('/create'),
            'edit' => Pages\EditFile::route('/{record}/edit'),
        ];
    }
}