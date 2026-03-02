<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerInvoiceResource\Pages;
use App\Models\ServerInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerInvoiceResource extends Resource
{
    protected static ?string $model = ServerInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->relationship('user', 'name')->searchable()->required(),
            Forms\Components\Select::make('order_id')->relationship('order', 'server_name')->required(),
            Forms\Components\TextInput::make('amount')->numeric()->prefix('€')->required(),
            Forms\Components\Select::make('period')
                ->options(['daily' => 'Tag', 'weekly' => 'Woche', 'monthly' => 'Monat', 'quarterly' => 'Quartal']),
            Forms\Components\DatePicker::make('period_start'),
            Forms\Components\DatePicker::make('period_end'),
            Forms\Components\Select::make('status')
                ->options(['pending' => 'Ausstehend', 'paid' => 'Bezahlt', 'failed' => 'Fehlgeschlagen', 'refunded' => 'Erstattet']),
            Forms\Components\TextInput::make('payment_method'),
            Forms\Components\TextInput::make('payment_transaction_id'),
            Forms\Components\DateTimePicker::make('paid_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nr.')
                    ->getStateUsing(fn (ServerInvoice $r) => $r->getInvoiceNumber()),
                Tables\Columns\TextColumn::make('user.name')->label('Kunde')->searchable(),
                Tables\Columns\TextColumn::make('order.server_name')->label('Server')->limit(20),
                Tables\Columns\TextColumn::make('amount')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('period')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'daily' => 'Tag', 'weekly' => 'Woche', 'monthly' => 'Monat', 'quarterly' => 'Quartal', default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'info', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')->dateTime('d.m.Y H:i')->label('Bezahlt am'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y')->label('Erstellt'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Ausstehend', 'paid' => 'Bezahlt', 'failed' => 'Fehlgeschlagen']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerInvoices::route('/'),
            'edit' => Pages\EditServerInvoice::route('/{record}/edit'),
        ];
    }
}
