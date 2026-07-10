<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\DonationResource\Pages\ListDonations;
use App\Filament\Resources\DonationResource\Pages\CreateDonation;
use App\Filament\Resources\DonationResource\Pages\EditDonation;
use App\Filament\Resources\DonationResource\Pages\DonationSettings;
use App\Filament\Resources\DonationResource\Pages;
use App\Models\Donation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';
    protected static string | \UnitEnum | null $navigationGroup = 'Donations';
    protected static ?int $navigationSort = 1;



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Donation Details')
                ->schema([
                    Select::make('user_id')
                        ->label('User (optional)')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->nullable(),
                    TextInput::make('donor_name')->maxLength(255),
                    TextInput::make('donor_email')->email()->maxLength(255),
                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('€')
                        ->step(0.01),
                    Select::make('currency')
                        ->options(['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP'])
                        ->default('EUR'),
                    Select::make('source')
                        ->options(['paypal' => 'PayPal', 'stripe' => 'Stripe', 'manual' => 'Manual', 'other' => 'Other'])
                        ->default('manual')
                        ->required(),
                    Select::make('status')
                        ->options(['pending' => 'Pending', 'completed' => 'Completed', 'refunded' => 'Refunded'])
                        ->default('completed')
                        ->required(),
                ])->columns(2),

            Section::make('Message & Display')
                ->schema([
                    Textarea::make('message')->rows(2),
                    TextInput::make('transaction_id')->maxLength(255),
                    Toggle::make('is_anonymous')->default(false),
                    Toggle::make('show_on_wall')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable()->label('Date'),
                TextColumn::make('display_name')->label('Donor')->searchable(['donor_name', 'donor_email']),
                TextColumn::make('amount')->money('EUR')->sortable(),
                TextColumn::make('source')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paypal' => 'info',
                        'stripe' => 'success',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('message')->limit(30)->toggleable(),
                IconColumn::make('is_anonymous')->boolean()->label('Anon'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->options(['paypal' => 'PayPal', 'stripe' => 'Stripe', 'manual' => 'Manual']),
                SelectFilter::make('status')
                    ->options(['completed' => 'Completed', 'pending' => 'Pending', 'refunded' => 'Refunded']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonations::route('/'),
            'create' => CreateDonation::route('/create'),
            'edit' => EditDonation::route('/{record}/edit'),
            'settings' => DonationSettings::route('/settings'),
        ];
    }
}
