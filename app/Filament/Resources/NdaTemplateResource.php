<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NdaTemplateResource\Pages\CreateNdaTemplate;
use App\Filament\Resources\NdaTemplateResource\Pages\EditNdaTemplate;
use App\Filament\Resources\NdaTemplateResource\Pages\ListNdaTemplates;
use App\Models\NdaTemplate;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NdaTemplateResource extends Resource
{
    protected static ?string $model = NdaTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'NDA Vorlagen';

    protected static ?string $modelLabel = 'NDA Vorlage';

    protected static ?string $pluralModelLabel = 'NDA Vorlagen';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Bezeichnung')
                ->required()
                ->maxLength(255),

            Select::make('locale')
                ->label('Sprache')
                ->options([
                    'de' => 'Deutsch',
                    'en' => 'English',
                ])
                ->required(),

            TextInput::make('version')
                ->label('Version')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required()
                ->helperText('Pro Sprache eindeutig. Bestehende Vertraege bleiben an ihrer Version haengen.'),

            Toggle::make('is_active')
                ->label('Aktiv')
                ->helperText('Nur die aktive Vorlage wird fuer neue Einladungen verwendet.')
                ->default(false),

            Textarea::make('body')
                ->label('Vertragstext (Markdown)')
                ->required()
                ->rows(30)
                ->columnSpanFull()
                ->helperText('Platzhalter im Format {{ $volunteer_name }}. Verfuegbar: version, version_date, volunteer_name, volunteer_username, volunteer_email, volunteer_discord, volunteer_birthdate, volunteer_country, role_name, permissions_list, penalty_amount, log_retention_months, authoritative_language, signed_at, signed_ip, signed_user_agent'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('locale')
                    ->label('Sprache')
                    ->badge()
                    ->sortable(),

                TextColumn::make('version')
                    ->label('Version')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                TextColumn::make('ndas_count')
                    ->counts('ndas')
                    ->label('Unterschriften'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNdaTemplates::route('/'),
            'create' => CreateNdaTemplate::route('/create'),
            'edit' => EditNdaTemplate::route('/{record}/edit'),
        ];
    }
}
