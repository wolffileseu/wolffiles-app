<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Storage;
use Exception;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\DemoResource\Pages\ListDemos;
use App\Filament\Resources\DemoResource\Pages\CreateDemo;
use App\Filament\Resources\DemoResource\Pages\EditDemo;
use App\Filament\Resources\DemoResource\Pages;
use App\Models\Demo;
use App\Models\Tag;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DemoResource extends Resource
{
    protected static ?string $model = Demo::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-film';
    protected static string | \UnitEnum | null $navigationGroup = 'Files';
    protected static ?string $navigationLabel = 'Demos';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Demo')->tabs([

                Tab::make('Content')->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')->maxLength(255)->unique(ignoreRecord: true),
                    Select::make('category_id')
                        ->relationship('category', 'name', fn ($query) => $query->where('type', 'demo'))
                        ->searchable()->required(),
                    Select::make('user_id')
                        ->relationship('user', 'name')->searchable()->required(),
                    Textarea::make('description')->rows(4)->columnSpanFull(),
                ])->columns(2),

                Tab::make('Game Info')->schema([
                    Select::make('game')->options([
                        'ET' => 'Enemy Territory', 'RtCW' => 'Return to Castle Wolfenstein',
                        'Q3' => 'Quake 3 Arena', 'ETQW' => 'ET: Quake Wars',
                    ])->default('ET')->required(),
                    TextInput::make('map_name')->maxLength(100)->placeholder('e.g. supply, goldrush, oasis'),
                    Select::make('mod_name')->options([
                        'etpro' => 'ETPro', 'jaymod' => 'Jaymod', 'nitmod' => 'N!tmod',
                        'legacy' => 'ET: Legacy', 'silent' => 'Silent Mod', 'noquarter' => 'NoQuarter',
                        'shrub' => 'Shrub', 'etpub' => 'ETPub', 'etjump' => 'ETJump', 'tce' => 'True Combat: Elite',
                    ])->searchable()->placeholder('Select mod...'),
                    Select::make('gametype')->options([
                        'stopwatch' => 'Stopwatch', 'objective' => 'Objective', 'lms' => 'Last Man Standing',
                        'ctf' => 'Capture the Flag', 'dm' => 'Deathmatch', 'other' => 'Other',
                    ]),
                    Select::make('match_format')->options([
                        '6on6' => '6on6', '5on5' => '5on5', '3on3' => '3on3',
                        '2on2' => '2on2', '1on1' => '1on1', 'public' => 'Public', 'other' => 'Other',
                    ]),
                    Select::make('demo_format')->options([
                        'dm_84' => 'dm_84 (ET 2.60b)', 'dm_83' => 'dm_83 (ET 2.56)',
                        'dm_82' => 'dm_82 (ET 2.55)', 'dm_60' => 'dm_60 (RtCW)', 'tv_84' => 'tv_84 (ETTV)',
                    ]),
                    TextInput::make('duration_seconds')->label('Duration (seconds)')->numeric(),
                    TextInput::make('server_name')->maxLength(255),
                ])->columns(2),

                Tab::make('Match Info')->schema([
                    TextInput::make('team_axis')->label('Team Axis / Team 1')->maxLength(100)->placeholder('e.g. fnatic, idle, anexis'),
                    TextInput::make('team_allies')->label('Team Allies / Team 2')->maxLength(100),
                    DatePicker::make('match_date')->label('Match Date'),
                    TextInput::make('recorder_name')->label('Recorded by')->maxLength(100),
                    TextInput::make('match_source')->label('Source')->placeholder('e.g. GamesTV, ESL, ClanBase')->maxLength(255),
                    TextInput::make('match_source_url')->label('Source URL')->url()->maxLength(255),
                ])->columns(2),

                Tab::make('File Info')->schema([
                    TextInput::make('file_name'),
                    TextInput::make('file_extension'),
                    TextInput::make('file_size')->numeric(),
                    TextInput::make('file_hash')->label('SHA256'),
                    FileUpload::make('file_path')->label('Demo File')
                        ->disk('s3')->directory('demos')->visibility('private')->maxSize(512000),
                ])->columns(2),

                Tab::make('Tags')->schema([
                    Select::make('tags')->label('Tags')->multiple()
                        ->relationship('tags', 'name')->preload()->searchable()
                        ->createOptionForm([
                            TextInput::make('name')->required()->maxLength(50)->unique('tags', 'name'),
                        ])
                        ->helperText('Tags: Clanwar, Official, POV, ETTV, Highlights, Anticheat, Trickjump, Fragmovie, etc.'),
                ]),

                Tab::make('Screenshots')->schema([
                    Placeholder::make('current_screenshots')
                        ->label('Current Screenshots')
                        ->content(function (?Demo $record): HtmlString {
                            if (!$record || $record->screenshots->isEmpty()) {
                                return new HtmlString('<p class="text-gray-500">No screenshots yet.</p>');
                            }
                            $html = '<div style="display: flex; flex-wrap: wrap; gap: 12px;">';
                            foreach ($record->screenshots as $ss) {
                                try {
                                    $url = Storage::disk('s3')->temporaryUrl($ss->path, now()->addHour());
                                    $html .= '<img src="' . e($url) . '" style="width: 160px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #374151;">';
                                } catch (Exception $e) {
                                    $html .= '<div style="width: 160px; height: 100px; background: #374151; border-radius: 8px;"></div>';
                                }
                            }
                            $html .= '</div>';
                            return new HtmlString($html);
                        })->visible(fn (?Demo $record) => $record !== null),
                    FileUpload::make('new_screenshots')->label('Add Screenshots')
                        ->multiple()->image()->maxSize(10240)->maxFiles(10)
                        ->disk('s3')->directory('demo-screenshots/temp')->visibility('public')->dehydrated(false),
                ]),

                Tab::make('Status')->schema([
                    Select::make('status')->options([
                        'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                    ])->required(),
                    Textarea::make('rejection_reason'),
                    Toggle::make('is_featured'),
                    TextInput::make('featured_label')->maxLength(50),
                    Toggle::make('virus_clean'),
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
                TextColumn::make('game')->badge()->color('info')->sortable(),
                TextColumn::make('map_name')->searchable()->sortable()->toggleable(),
                TextColumn::make('mod_name')->label('Mod')->badge()->color('warning')->toggleable(),
                TextColumn::make('demo_format')->label('Format')->badge()->toggleable(),
                TextColumn::make('match_format')->label('Match')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 1) . ' MB' : '-'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray',
                }),
                TextColumn::make('download_count')->sortable(),
                TextColumn::make('match_date')->date('d.m.Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                SelectFilter::make('game')->options(['ET' => 'Enemy Territory', 'RtCW' => 'RtCW', 'Q3' => 'Quake 3', 'ETQW' => 'ET: Quake Wars']),
                SelectFilter::make('mod_name')->label('Mod')->options([
                    'etpro' => 'ETPro', 'jaymod' => 'Jaymod', 'nitmod' => 'N!tmod',
                    'legacy' => 'ET: Legacy', 'silent' => 'Silent Mod', 'noquarter' => 'NoQuarter',
                ]),
                SelectFilter::make('demo_format')->label('Demo Format')->options([
                    'dm_84' => 'dm_84', 'dm_83' => 'dm_83', 'tv_84' => 'tv_84', 'dm_60' => 'dm_60',
                ]),
                SelectFilter::make('category_id')->relationship('category', 'name')->label('Category'),
                SelectFilter::make('tags')->relationship('tags', 'name')->multiple()->preload()->label('Tags'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
        /** @var \App\Models\Demo $record */
                    ->visible(fn (Demo $record) => $record->status === 'pending')->requiresConfirmation()
                    ->action(function (Demo $record) {
                        $record->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'published_at' => $record->published_at ?? now()]);
                        Notification::make()->title('Demo approved!')->success()->send();
                    }),
                Action::make('reject')->label('Reject')->icon('heroicon-o-x-circle')->color('danger')
        /** @var \App\Models\Demo $record */
                    ->visible(fn (Demo $record) => $record->status === 'pending')
                    ->schema([Textarea::make('rejection_reason')->label('Reason')->required()])
                    ->action(function (Demo $record, array $data) {
                        $record->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
                        Notification::make()->title('Demo rejected.')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('bulk_approve')->label('Approve Selected')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
        /** @var Demo $record */
                            if ($record->status === 'pending') {
                                $record->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'published_at' => $record->published_at ?? now()]);
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} demos approved!")->success()->send();
                    }),
                BulkAction::make('bulk_reject')->label('Reject Selected')->icon('heroicon-o-x-circle')->color('danger')
                    ->form([Textarea::make('rejection_reason')->label('Reason (applies to all)')->required()])
                    ->requiresConfirmation()->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
        /** @var Demo $record */
                            if ($record->status === 'pending') {
                                $record->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} demos rejected.")->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemos::route('/'),
            'create' => CreateDemo::route('/create'),
            'edit' => EditDemo::route('/{record}/edit'),
        ];
    }
}
