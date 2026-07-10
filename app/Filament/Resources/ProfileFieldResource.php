<?php
namespace App\Filament\Resources;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ProfileFieldResource\Pages\ListProfileFields;
use App\Filament\Resources\ProfileFieldResource\Pages\CreateProfileField;
use App\Filament\Resources\ProfileFieldResource\Pages\EditProfileField;
use App\Filament\Resources\ProfileFieldResource\Pages;
use App\Models\ProfileField;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProfileFieldResource extends Resource {
    protected static ?string $model = ProfileField::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Community';
    protected static ?string $navigationLabel = 'Profilfelder';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('label')->required()->label('Bezeichnung')->placeholder('z.B. ET Server'),
                TextInput::make('key')->required()->unique(ignoreRecord:true)->label('Schlüssel')->placeholder('et_server')->helperText('Nur Buchstaben, Zahlen, Unterstriche'),
                Select::make('type')->options(['text'=>'Text','url'=>'URL','textarea'=>'Textarea','select'=>'Auswahl'])->default('text')->required()->label('Typ')->live(),
                TextInput::make('placeholder')->label('Platzhalter'),
                Textarea::make('options')->label('Optionen (eine pro Zeile)')->visible(fn($get)=>$get('type')==='select')->helperText('Eine Option pro Zeile'),
                TextInput::make('sort_order')->numeric()->default(0)->label('Reihenfolge'),
                Toggle::make('is_active')->default(true)->label('Aktiv'),
                Toggle::make('is_required')->default(false)->label('Pflichtfeld'),
                Toggle::make('show_on_profile')->default(true)->label('Auf Profil anzeigen'),
            ]),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('label')->label('Bezeichnung')->searchable(),
            TextColumn::make('key')->label('Schlüssel')->badge(),
            TextColumn::make('type')->label('Typ')->badge(),
            IconColumn::make('is_active')->label('Aktiv')->boolean(),
            IconColumn::make('show_on_profile')->label('Profil')->boolean(),
            IconColumn::make('is_required')->label('Pflicht')->boolean(),
        ])
        ->defaultSort('sort_order')
        ->reorderable('sort_order')
        ->recordActions([EditAction::make(), DeleteAction::make()])
        ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array {
        return [
            'index' => ListProfileFields::route('/'),
            'create' => CreateProfileField::route('/create'),
            'edit' => EditProfileField::route('/{record}/edit'),
        ];
    }
}
