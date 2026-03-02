<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerNodeResource\Pages;
use App\Models\ServerNode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerNodeResource extends Resource
{
    protected static ?string $model = ServerNode::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Nodes';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Node Info')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->placeholder('Node DE-1 Falkenstein'),
                    Forms\Components\TextInput::make('pterodactyl_node_id')->numeric()->required()->label('Pterodactyl Node ID'),
                    Forms\Components\TextInput::make('fqdn')->placeholder('node1.wolffiles.eu')->label('FQDN'),
                    Forms\Components\TextInput::make('location')->default('DE')->maxLength(5),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),
            Forms\Components\Section::make('Kapazität')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('memory_total_mb')->numeric()->suffix('MB')->required()->label('Total RAM'),
                    Forms\Components\TextInput::make('memory_allocated_mb')->numeric()->suffix('MB')->default(0)->label('Allocated RAM'),
                    Forms\Components\TextInput::make('disk_total_mb')->numeric()->suffix('MB')->required()->label('Total Disk'),
                    Forms\Components\TextInput::make('disk_allocated_mb')->numeric()->suffix('MB')->default(0)->label('Allocated Disk'),
                    Forms\Components\TextInput::make('max_servers')->numeric()->default(30)->label('Max Servers'),
                    Forms\Components\TextInput::make('active_servers')->numeric()->default(0)->label('Active Servers'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->badge()
                    ->formatStateUsing(fn ($state) => '📍 ' . $state),
                Tables\Columns\TextColumn::make('fqdn')->size('sm'),
                Tables\Columns\TextColumn::make('memory_usage')
                    ->label('RAM')
                    ->getStateUsing(fn (ServerNode $r) => round($r->memory_allocated_mb / 1024, 1) . ' / ' . round($r->memory_total_mb / 1024, 1) . ' GB'),
                Tables\Columns\TextColumn::make('disk_usage')
                    ->label('Disk')
                    ->getStateUsing(fn (ServerNode $r) => round($r->disk_allocated_mb / 1024, 1) . ' / ' . round($r->disk_total_mb / 1024, 1) . ' GB'),
                Tables\Columns\TextColumn::make('servers')
                    ->label('Servers')
                    ->getStateUsing(fn (ServerNode $r) => $r->active_servers . ' / ' . $r->max_servers),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\IconColumn::make('is_full')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerNodes::route('/'),
            'create' => Pages\CreateServerNode::route('/create'),
            'edit' => Pages\EditServerNode::route('/{record}/edit'),
        ];
    }
}
