<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ClanResource\Pages;
use App\Models\Clan;
use App\Models\Tracker\TrackerClan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanResource extends Resource
{
    protected static ?string $model = Clan::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Clans';
    protected static ?int $navigationSort = 1;

    /**
     * Disable direct creation — the only way to create a clans row is via
     * the claim flow (TrackerClaim::approve() -> linkRegisteredClan()).
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ---------- Identity ----------
            Forms\Components\Section::make('Identity')->schema([
                Forms\Components\Select::make('tracker_clan_id')
                    ->label('Linked Tracker Clan')
                    ->relationship('trackerClan', 'tag_clean')
                    ->getOptionLabelFromRecordUsing(fn (TrackerClan $r) => "[{$r->tag_clean}] " . ($r->name ?? '(no name)') . " — " . $r->member_count . ' members')
                    ->searchable(['tag_clean', 'name'])
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('1:1 link, set by the claim flow. Not editable here.'),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('tag')->required()->maxLength(10)
                    ->prefix('[')->suffix(']')
                    ->helperText('Display tag. To match more name patterns, edit tracker_clan.tag_clean instead.'),
                Forms\Components\TextInput::make('slug')->required()->maxLength(50)
                    ->regex('/^[a-z][a-z0-9-]+$/')
                    ->rule('not_in:manage,propose,recruiting,create,edit,delete,admin,new,tracker,clans')
                    ->unique(ignoreRecord: true)
                    ->helperText('URL slug. Admin bypass: no 30-day lock here, change freely.'),
            ])->columns(2),

            // ---------- Status ----------
            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true)->inline(false)
                    ->helperText('Soft deactivation. Inactive clans are hidden everywhere.'),
                Forms\Components\Toggle::make('is_published')->label('Published')->default(false)->inline(false)
                    ->helperText('Visible on public clan page.'),
                Forms\Components\Toggle::make('is_recruiting')->label('Recruiting')->default(false)->inline(false)
                    ->helperText('Appears on /clans/recruiting board.'),
            ])->columns(3),

            // ---------- Profile content ----------
            Forms\Components\Section::make('Profile')->schema([
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull()
                    ->helperText('Markdown + BBCode supported.'),
                Forms\Components\Textarea::make('rules')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('recruitment_summary')->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('location')->maxLength(255),
                Forms\Components\TextInput::make('founded')->maxLength(50)->placeholder('e.g. 2008'),
            ])->columns(2),

            // ---------- Contact / Links ----------
            Forms\Components\Section::make('Contact & Links')->schema([
                Forms\Components\TextInput::make('website')->url()->maxLength(255),
                Forms\Components\TextInput::make('contact_email')->email()->maxLength(255),
                Forms\Components\TextInput::make('contact_discord')->maxLength(255)->prefix('discord.gg/'),
                Forms\Components\TextInput::make('ts_address')->maxLength(255)->placeholder('ts3.example.com:9987'),
            ])->columns(2),

            // ---------- Images ----------
            Forms\Components\Section::make('Images')->schema([
                Forms\Components\TextInput::make('logo')->label('Logo URL')->maxLength(500)
                    ->placeholder('https://... or storage path')
                    ->helperText('External URL or local storage path. Auto-detected at render time.'),
                Forms\Components\TextInput::make('banner')->label('Banner URL')->maxLength(500)
                    ->placeholder('https://... or storage path'),
            ])->columns(2),

            // ---------- Meta (read-only) ----------
            Forms\Components\Section::make('Meta')->schema([
                Forms\Components\TextInput::make('view_count')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('created_at')->disabled()->dehydrated(false),
            ])->columns(2)->collapsible()->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->circular()->size(40)
                    ->getStateUsing(fn ($record) => $record->logo
                        ? (\Illuminate\Support\Str::startsWith($record->logo, ['http://','https://'])
                            ? $record->logo
                            : asset('storage/'.$record->logo))
                        : null),
                Tables\Columns\TextColumn::make('tag')->badge()->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('trackerClan.member_count')->label('Members')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->label('Owner')
                    ->getStateUsing(function ($record) {
                        $owner = $record->managers()->where('role', 'leader')->with('user')->first();
                        return $owner?->user?->name ?? '—';
                    }),
                Tables\Columns\IconColumn::make('is_recruiting')->boolean()->label('Recruiting'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('apiKeys_count')->counts('apiKeys')->label('API Keys'),
                Tables\Columns\TextColumn::make('posts_count')->counts('posts')->label('Posts'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
                Tables\Filters\TernaryFilter::make('is_recruiting')->label('Recruiting'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('viewPublic')->label('View public')
                    ->icon('heroicon-o-globe-alt')->color('gray')
                    ->url(fn ($record) => $record->trackerClan ? route('tracker.clan.show', $record->trackerClan->id) : null, shouldOpenInNewTab: true)
                    ->visible(fn ($record) => $record->trackerClan !== null),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ClanResource\RelationManagers\ManagersRelationManager::class,
            \App\Filament\Resources\ClanResource\RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClans::route('/'),
            'edit'  => Pages\EditClan::route('/{record}/edit'),
        ];
    }
}
