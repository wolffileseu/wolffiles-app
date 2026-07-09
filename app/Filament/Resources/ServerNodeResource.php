<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use App\Filament\Resources\ServerNodeResource\Pages\ListServerNodes;
use App\Filament\Resources\ServerNodeResource\Pages\CreateServerNode;
use App\Filament\Resources\ServerNodeResource\Pages\EditServerNode;
use App\Filament\Resources\ServerNodeResource\Pages;
use App\Models\ServerNode;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerNodeResource extends Resource
{
    protected static ?string $model = ServerNode::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string | \UnitEnum | null $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Nodes';
    protected static ?int $navigationSort = 3;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Node Info')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->placeholder('Node DE-1 Falkenstein'),
                    TextInput::make('pterodactyl_node_id')->numeric()->required()->label('Pterodactyl Node ID'),
                    TextInput::make('fqdn')->placeholder('node1.wolffiles.eu')->label('FQDN'),
                    TextInput::make('location')->default('DE')->maxLength(5),
                    Toggle::make('is_active')->default(true),
                ]),
            Section::make('Kapazität')
                ->columns(2)
                ->schema([
                    TextInput::make('memory_total_mb')->numeric()->suffix('MB')->required()->label('Total RAM'),
                    TextInput::make('memory_allocated_mb')->numeric()->suffix('MB')->default(0)->label('Allocated RAM'),
                    TextInput::make('disk_total_mb')->numeric()->suffix('MB')->required()->label('Total Disk'),
                    TextInput::make('disk_allocated_mb')->numeric()->suffix('MB')->default(0)->label('Allocated Disk'),
                    TextInput::make('max_servers')->numeric()->default(30)->label('Max Servers'),
                    TextInput::make('active_servers')->numeric()->default(0)->label('Active Servers'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('location')
                    ->badge()
                    ->formatStateUsing(fn ($state) => '📍 ' . $state),
                TextColumn::make('fqdn')->size('sm'),
                TextColumn::make('memory_usage')
                    ->label('RAM')
                    ->getStateUsing(fn (ServerNode $r) => round($r->memory_allocated_mb / 1024, 1) . ' / ' . round($r->memory_total_mb / 1024, 1) . ' GB'),
                TextColumn::make('disk_usage')
                    ->label('Disk')
                    ->getStateUsing(fn (ServerNode $r) => round($r->disk_allocated_mb / 1024, 1) . ' / ' . round($r->disk_total_mb / 1024, 1) . ' GB'),
                TextColumn::make('servers')
                    ->label('Servers')
                    ->getStateUsing(fn (ServerNode $r) => $r->active_servers . ' / ' . $r->max_servers),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_full')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServerNodes::route('/'),
            'create' => CreateServerNode::route('/create'),
            'edit' => EditServerNode::route('/{record}/edit'),
        ];
    }
}
