<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Str;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClanResource\RelationManagers\ManagersRelationManager;
use App\Filament\Resources\ClanResource\RelationManagers\MembersRelationManager;
use App\Filament\Resources\ClanResource\Pages\ListClans;
use App\Filament\Resources\ClanResource\Pages\EditClan;
use App\Filament\Resources\ClanResource\Pages;
use App\Models\Clan;
use App\Models\Tracker\TrackerClan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanResource extends Resource
{
    protected static ?string $model = Clan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static string | \UnitEnum | null $navigationGroup = 'Clans';
    protected static ?int $navigationSort = 1;

    /**
     * Disable direct creation — the only way to create a clans row is via
     * the claim flow (TrackerClaim::approve() -> linkRegisteredClan()).
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ---------- Identity ----------
            Section::make('Identity')->schema([
                Select::make('tracker_clan_id')
                    ->label('Linked Tracker Clan')
                    ->relationship('trackerClan', 'tag_clean')
                    ->getOptionLabelFromRecordUsing(fn (TrackerClan $r) => "[{$r->tag_clean}] " . ($r->name ?? '(no name)') . " — " . $r->member_count . ' members')
                    ->searchable(['tag_clean', 'name'])
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('1:1 link, set by the claim flow. Not editable here.'),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('tag')->required()->maxLength(10)
                    ->prefix('[')->suffix(']')
                    ->helperText('Display tag. To match more name patterns, edit tracker_clan.tag_clean instead.'),
                TextInput::make('slug')->required()->maxLength(50)
                    ->regex('/^[a-z][a-z0-9-]+$/')
                    ->rule('not_in:manage,propose,recruiting,create,edit,delete,admin,new,tracker,clans')
                    ->unique(ignoreRecord: true)
                    ->helperText('URL slug. Admin bypass: no 30-day lock here, change freely.'),
            ])->columns(2),

            // ---------- Status ----------
            Section::make('Status')->schema([
                Toggle::make('is_active')->label('Active')->default(true)->inline(false)
                    ->helperText('Soft deactivation. Inactive clans are hidden everywhere.'),
                Toggle::make('is_published')->label('Published')->default(false)->inline(false)
                    ->helperText('Visible on public clan page.'),
                Toggle::make('is_recruiting')->label('Recruiting')->default(false)->inline(false)
                    ->helperText('Appears on /clans/recruiting board.'),
            ])->columns(3),

            // ---------- Profile content ----------
            Section::make('Profile')->schema([
                Textarea::make('description')->rows(3)->columnSpanFull()
                    ->helperText('Markdown + BBCode supported.'),
                Textarea::make('rules')->rows(4)->columnSpanFull(),
                Textarea::make('recruitment_summary')->rows(3)->columnSpanFull(),
                TextInput::make('location')->maxLength(255),
                TextInput::make('founded')->maxLength(50)->placeholder('e.g. 2008'),
                Select::make('founded_label')->label('Date label')
                    ->options(['founded' => 'founded …', 'since' => 'since …', 'established' => 'established in …'])
                    ->default('founded')->selectablePlaceholder(false)
                    ->helperText('How the date is introduced on the public profile.'),
            ])->columns(2),

            // ---------- Contact / Links ----------
            Section::make('Contact & Links')->schema([
                TextInput::make('website')->url()->maxLength(255),
                TextInput::make('contact_email')->email()->maxLength(255),
                TextInput::make('contact_discord')->maxLength(255)->prefix('discord.gg/'),
                TextInput::make('ts_address')->maxLength(255)->placeholder('ts3.example.com:9987'),
            ])->columns(2),

            // ---------- Images ----------
            Section::make('Images')->schema([
                TextInput::make('logo')->label('Logo URL')->maxLength(500)
                    ->placeholder('https://... or storage path')
                    ->helperText('External URL or local storage path. Auto-detected at render time.'),
                TextInput::make('banner')->label('Banner URL')->maxLength(500)
                    ->placeholder('https://... or storage path'),
            ])->columns(2),

            // ---------- Meta (read-only) ----------
            Section::make('Meta')->schema([
                TextInput::make('view_count')->disabled()->dehydrated(false),
                TextInput::make('created_at')->disabled()->dehydrated(false),
            ])->columns(2)->collapsible()->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->circular()->size(40)
                    ->getStateUsing(fn ($record) => $record->logo
                        ? (Str::startsWith($record->logo, ['http://','https://'])
                            ? $record->logo
                            : asset('storage/'.$record->logo))
                        : null),
                TextColumn::make('tag')->badge()->color('primary')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('trackerClan.member_count')->label('Members')->numeric()->sortable(),
                TextColumn::make('owner_name')
                    ->label('Owner')
                    ->getStateUsing(function ($record) {
                        $owner = $record->managers()->where('role', 'leader')->with('user')->first();
                        return $owner?->user?->name ?? '—';
                    }),
                IconColumn::make('is_recruiting')->boolean()->label('Recruiting'),
                IconColumn::make('is_published')->boolean()->label('Published'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('apiKeys_count')->counts('apiKeys')->label('API Keys'),
                TextColumn::make('posts_count')->counts('posts')->label('Posts'),
                TextColumn::make('created_at')->dateTime('d.m.Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('is_recruiting')->label('Recruiting'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('viewPublic')->label('View public')
                    ->icon('heroicon-o-globe-alt')->color('gray')
                    ->url(fn ($record) => $record->trackerClan ? route('tracker.clan.show', $record->trackerClan->id) : null, shouldOpenInNewTab: true)
                    ->visible(fn ($record) => $record->trackerClan !== null),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ManagersRelationManager::class,
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClans::route('/'),
            'edit'  => EditClan::route('/{record}/edit'),
        ];
    }
}
