<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BadgeResource\Pages\ListBadges;
use App\Filament\Resources\BadgeResource\Pages\CreateBadge;
use App\Filament\Resources\BadgeResource\Pages\EditBadge;
use App\Filament\Resources\BadgeResource\Pages;
use App\Models\Badge;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';
    protected static string | \UnitEnum | null $navigationGroup = 'Community';




    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            Textarea::make('description'),
            TextInput::make('icon')->helperText('SVG or icon class'),
            ColorPicker::make('color')->default('#FFD700'),
            Select::make('criteria_type')->options([
                'uploads_count' => 'Upload Count',
                'downloads_total' => 'Total Downloads on Files',
                'first_upload' => 'First Upload',
                'rating_given' => 'Ratings Given',
                'comments_count' => 'Comments Count',
                'manual' => 'Manual Assignment',
            ])->required(),
            TextInput::make('criteria_value')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable(),
                ColorColumn::make('color'),
                TextColumn::make('criteria_type')->badge(),
                TextColumn::make('criteria_value'),
                TextColumn::make('users_count')->counts('users')->label('Awarded'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBadges::route('/'),
            'create' => CreateBadge::route('/create'),
            'edit' => EditBadge::route('/{record}/edit'),
        ];
    }
}
