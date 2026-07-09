<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use App\Filament\Resources\ServerInvoiceResource\Pages\ListServerInvoices;
use App\Filament\Resources\ServerInvoiceResource\Pages\EditServerInvoice;
use App\Filament\Resources\ServerInvoiceResource\Pages;
use App\Models\ServerInvoice;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerInvoiceResource extends Resource
{
    protected static ?string $model = ServerInvoice::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?int $navigationSort = 4;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->relationship('user', 'name')->searchable()->required(),
            Select::make('order_id')->relationship('order', 'server_name')->required(),
            TextInput::make('amount')->numeric()->prefix('€')->required(),
            Select::make('period')
                ->options(['daily' => 'Tag', 'weekly' => 'Woche', 'monthly' => 'Monat', 'quarterly' => 'Quartal']),
            DatePicker::make('period_start'),
            DatePicker::make('period_end'),
            Select::make('status')
                ->options(['pending' => 'Ausstehend', 'paid' => 'Bezahlt', 'failed' => 'Fehlgeschlagen', 'refunded' => 'Erstattet']),
            TextInput::make('payment_method'),
            TextInput::make('payment_transaction_id'),
            DateTimePicker::make('paid_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nr.')
                    ->getStateUsing(fn (ServerInvoice $r) => $r->getInvoiceNumber()),
                TextColumn::make('user.name')->label('Kunde')->searchable(),
                TextColumn::make('order.server_name')->label('Server')->limit(20),
                TextColumn::make('amount')->money('EUR')->sortable(),
                TextColumn::make('period')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'daily' => 'Tag', 'weekly' => 'Woche', 'monthly' => 'Monat', 'quarterly' => 'Quartal', default => $state,
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'info', default => 'gray',
                    }),
                TextColumn::make('paid_at')->dateTime('d.m.Y H:i')->label('Bezahlt am'),
                TextColumn::make('created_at')->dateTime('d.m.Y')->label('Erstellt'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Ausstehend', 'paid' => 'Bezahlt', 'failed' => 'Fehlgeschlagen']),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServerInvoices::route('/'),
            'edit' => EditServerInvoice::route('/{record}/edit'),
        ];
    }
}
