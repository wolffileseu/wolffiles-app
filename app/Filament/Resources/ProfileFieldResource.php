<?php
namespace App\Filament\Resources;
use App\Filament\Resources\ProfileFieldResource\Pages;
use App\Models\ProfileField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProfileFieldResource extends Resource {
    protected static ?string $model = ProfileField::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Community';
    protected static ?string $navigationLabel = 'Profilfelder';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('label')->required()->label('Bezeichnung')->placeholder('z.B. ET Server'),
                Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord:true)->label('Schlüssel')->placeholder('et_server')->helperText('Nur Buchstaben, Zahlen, Unterstriche'),
                Forms\Components\Select::make('type')->options(['text'=>'Text','url'=>'URL','textarea'=>'Textarea','select'=>'Auswahl'])->default('text')->required()->label('Typ')->live(),
                Forms\Components\TextInput::make('placeholder')->label('Platzhalter'),
                Forms\Components\Textarea::make('options')->label('Optionen (eine pro Zeile)')->visible(fn($get)=>$get('type')==='select')->helperText('Eine Option pro Zeile'),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0)->label('Reihenfolge'),
                Forms\Components\Toggle::make('is_active')->default(true)->label('Aktiv'),
                Forms\Components\Toggle::make('is_required')->default(false)->label('Pflichtfeld'),
                Forms\Components\Toggle::make('show_on_profile')->default(true)->label('Auf Profil anzeigen'),
            ]),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('label')->label('Bezeichnung')->searchable(),
            Tables\Columns\TextColumn::make('key')->label('Schlüssel')->badge(),
            Tables\Columns\TextColumn::make('type')->label('Typ')->badge(),
            Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
            Tables\Columns\IconColumn::make('show_on_profile')->label('Profil')->boolean(),
            Tables\Columns\IconColumn::make('is_required')->label('Pflicht')->boolean(),
        ])
        ->defaultSort('sort_order')
        ->reorderable('sort_order')
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListProfileFields::route('/'),
            'create' => Pages\CreateProfileField::route('/create'),
            'edit' => Pages\EditProfileField::route('/{record}/edit'),
        ];
    }
}
