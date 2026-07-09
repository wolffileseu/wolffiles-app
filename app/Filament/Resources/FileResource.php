<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Storage;
use Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use App\Filament\Resources\FileResource\RelationManagers\RelatedBotsRelationManager;
use App\Filament\Resources\FileResource\RelationManagers\RelatedMapsRelationManager;
use App\Filament\Resources\FileResource\Pages\ListFiles;
use App\Filament\Resources\FileResource\Pages\CreateFile;
use App\Filament\Resources\FileResource\Pages\EditFile;
use App\Filament\Resources\FileResource\Pages;
use App\Models\File;
use App\Models\Tag;
use App\Services\DiscordWebhookService;
use App\Services\SocialMedia\SocialMediaService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class FileResource extends Resource
{
    protected static ?string $model = File::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static string | \UnitEnum | null $navigationGroup = 'Files';
    protected static ?int $navigationSort = 1;



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('File')->tabs([

                // Tab 1: Content
                Tab::make('Content')->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')->maxLength(255)->unique(ignoreRecord: true),
                    Select::make('category_id')
                        ->relationship(
                            name: 'category',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->with('parent')->orderBy('parent_id')->orderBy('sort_order')->orderBy('name'),
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->parent ? "{$record->parent->name} → {$record->name}" : $record->name)
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required(),
                    Textarea::make('description')->rows(4),
                    RichEditor::make('description_html')->label('Description (Rich)'),
                ])->columns(2),

                // Tab 2: File Info
                Tab::make('File Info')->schema([
                    TextInput::make('file_name'),
                    TextInput::make('file_size')->numeric(),
                    TextInput::make('file_hash')->label('SHA256'),
                    TextInput::make('map_name'),
                    TextInput::make('version'),
                    TextInput::make('original_author'),
                    Select::make('game')
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
                Tab::make('Tags')->schema([
                    Select::make('tags')
                        ->label('Tags')
                        ->multiple()
                        ->relationship('tags', 'name')
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(50)
                                ->unique('tags', 'name'),
                        ])
                        ->helperText('Select existing tags or create new ones. Use tags like Objective, Frag, Trickjump, Sniper, etc.'),

                    Placeholder::make('suggested_tags')
                        ->label('Suggested Tags')
                        ->content('Map Type: Objective, Frag, Trickjump, Deathmatch, CTF, Last Man Standing — Style: Sniper, Panzer, Rifle, CQB, Indoor, Outdoor — Size: Small, Medium, Large — Theme: WW2, Desert, Snow, Urban, Forest, Beach, Night — Quality: Final, Beta, Competitive, Fun Map'),
                ]),

                // Tab 4: Screenshots
                Tab::make('Screenshots')->schema([
                    Placeholder::make('current_screenshots')
                        ->label('Current Screenshots')
                        ->content(function (?File $record): HtmlString {
                            if (!$record || $record->screenshots->isEmpty()) {
                                return new HtmlString('<p class="text-gray-500">No screenshots yet.</p>');
                            }

                            $html = '<div style="display: flex; flex-wrap: wrap; gap: 12px;">';
                            foreach ($record->screenshots as $screenshot) {
                                try {
                                    $url = Storage::disk('s3')->temporaryUrl($screenshot->path, now()->addHour());
                                    $html .= '<div style="position: relative;">';
                                    $html .= '<img src="' . e($url) . '" style="width: 160px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #374151;">';
                                    $html .= '<span style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px;">#' . $screenshot->id . '</span>';
                                    $html .= '</div>';
                                } catch (Exception $e) {
                                    $html .= '<div style="width: 160px; height: 100px; background: #374151; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 12px;">Error</div>';
                                }
                            }
                            $html .= '</div>';
                            return new HtmlString($html);
                        })
                        ->visible(fn (?File $record) => $record !== null),

                    FileUpload::make('new_screenshots')
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

                    TextInput::make('delete_screenshot_ids')
                        ->label('Delete Screenshots (IDs)')
                        ->helperText('Enter screenshot IDs to delete, separated by commas (e.g. "12,15,18"). See IDs on images above.')
                        ->placeholder('e.g. 12,15,18')
                        ->dehydrated(false),
                ]),

                // Tab 5: Status
                Tab::make('Status')->schema([
                    Select::make('status')
                        ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                        ->required(),
                    Textarea::make('rejection_reason'),
                    Toggle::make('is_featured'),
                    TextInput::make('featured_label')->maxLength(50),
                    Toggle::make('virus_clean'),
                    TextInput::make('virus_scan_result'),
                ])->columns(2),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->searchable()->sortable()->limit(40),
                TextColumn::make('user.name')->label('Uploader')->sortable(),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('game')->badge()->sortable(),
                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->color('warning')
                    ->separator(', ')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 1) . ' MB' : '-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('virus_clean')->boolean(),
                TextColumn::make('download_count')->sortable(),
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                SelectFilter::make('category_id')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->with('parent')->orderBy('parent_id')->orderBy('sort_order')->orderBy('name'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->parent ? "{$record->parent->name} → {$record->name}" : $record->name)
                    ->label('Category'),
                SelectFilter::make('game')
                    ->options([
                        'ET' => 'Enemy Territory',
                        'RtCW' => 'RtCW',
                        'ET Quake Wars' => 'ET Quake Wars',
                    ]),
                SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Tags'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('approve')
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

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
        /** @var \App\Models\File $record */
                    ->visible(fn (File $record) => $record->status === 'pending')
                    ->schema([
                        Textarea::make('rejection_reason')
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
            ->toolbarActions([
                DeleteBulkAction::make(),

                BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
        /** @var File $record */
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

                BulkAction::make('bulk_reject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason (applies to all)')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
        /** @var File $record */
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

                BulkAction::make('bulk_tag')
                    ->label('Add Tags')
                    ->icon('heroicon-o-tag')
                    ->color('warning')
                    ->form([
                        Select::make('tags')
                            ->label('Tags to add')
                            ->multiple()
                            ->options(Tag::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required()->maxLength(50),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $tag = Tag::firstOrCreate(
                                    ['slug' => Str::slug($data['name'])],
                                    ['name' => $data['name']]
                                );
                                return $tag->id;
                            }),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        if (!empty($data['tags'])) {
                            foreach ($records as $record) {
        /** @var File $record */
                                $record->tags()->syncWithoutDetaching($data['tags']);
                            }
                            Notification::make()->title('Tags added to ' . $records->count() . ' files!')->success()->send();
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelatedBotsRelationManager::class,
            RelatedMapsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiles::route('/'),
            'create' => CreateFile::route('/create'),
            'edit' => EditFile::route('/{record}/edit'),
        ];
    }
}