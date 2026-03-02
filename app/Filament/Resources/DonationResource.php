<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DonationResource\Pages;
use App\Models\Donation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Donations';
    protected static ?int $navigationSort = 1;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_donations');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Donation Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User (optional)')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->nullable(),
                    Forms\Components\TextInput::make('donor_name')->maxLength(255),
                    Forms\Components\TextInput::make('donor_email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('€')
                        ->step(0.01),
                    Forms\Components\Select::make('currency')
                        ->options(['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP'])
                        ->default('EUR'),
                    Forms\Components\Select::make('source')
                        ->options(['paypal' => 'PayPal', 'stripe' => 'Stripe', 'manual' => 'Manual', 'other' => 'Other'])
                        ->default('manual')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options(['pending' => 'Pending', 'completed' => 'Completed', 'refunded' => 'Refunded'])
                        ->default('completed')
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Message & Display')
                ->schema([
                    Forms\Components\Textarea::make('message')->rows(2),
                    Forms\Components\TextInput::make('transaction_id')->maxLength(255),
                    Forms\Components\Toggle::make('is_anonymous')->default(false),
                    Forms\Components\Toggle::make('show_on_wall')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable()->label('Date'),
                Tables\Columns\TextColumn::make('display_name')->label('Donor')->searchable(['donor_name', 'donor_email']),
                Tables\Columns\TextColumn::make('amount')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('source')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paypal' => 'info',
                        'stripe' => 'success',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')->limit(30)->toggleable(),
                Tables\Columns\IconColumn::make('is_anonymous')->boolean()->label('Anon'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(['paypal' => 'PayPal', 'stripe' => 'Stripe', 'manual' => 'Manual']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['completed' => 'Completed', 'pending' => 'Pending', 'refunded' => 'Refunded']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'create' => Pages\CreateDonation::route('/create'),
            'edit' => Pages\EditDonation::route('/{record}/edit'),
            'settings' => Pages\DonationSettings::route('/settings'),
        ];
    }
}
