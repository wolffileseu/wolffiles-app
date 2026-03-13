<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiRequestLogResource\Pages;
use App\Models\ApiRequestLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLogResource extends Resource
{
    protected static ?string $model = ApiRequestLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'API Logs';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'GET' => 'success',
                        'POST' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('endpoint')
                    ->label('Endpoint')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('query_string')
                    ->label('Query')
                    ->limit(40),
                Tables\Columns\TextColumn::make('client_type')
                    ->label('Client')
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'discord_bot'  => 'primary',
                        'telegram_bot' => 'success',
                        'curl'         => 'warning',
                        'python'       => 'info',
                        'browser'      => 'gray',
                        default        => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('response_ms')
                    ->label('ms')
                    ->suffix(' ms')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_type')
                    ->options([
                        'discord_bot'  => 'Discord Bot',
                        'telegram_bot' => 'Telegram Bot',
                        'curl'         => 'cURL',
                        'python'       => 'Python',
                        'browser'      => 'Browser',
                        'unknown'      => 'Unknown',
                        'other'        => 'Other',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiRequestLogs::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
}
