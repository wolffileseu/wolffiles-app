<?php

namespace App\Filament\Resources\DonationResource\Pages;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use App\Models\DonationSetting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use App\Filament\Resources\DonationResource;

/**
 * @property \Filament\Schemas\Schema $form
 */
class DonationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = DonationResource::class;
    protected string $view = 'filament.pages.donation-settings';
    protected static ?string $title = 'Donation Settings';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'monthly_goal' => DonationSetting::get('monthly_goal', '50'),
            'paypal_email' => DonationSetting::get('paypal_email', ''),
            'paypal_enabled' => DonationSetting::get('paypal_enabled', '1') === '1',
            'stripe_enabled' => DonationSetting::get('stripe_enabled', '0') === '1',
            'donation_message' => DonationSetting::get('donation_message', ''),
            'discord_webhook_url' => DonationSetting::get('discord_webhook_url', ''),
            'notification_email' => DonationSetting::get('notification_email', ''),
            'cost_servers' => DonationSetting::get('cost_servers', '25'),
            'cost_storage' => DonationSetting::get('cost_storage', '15'),
            'cost_domain' => DonationSetting::get('cost_domain', '5'),
            'cost_other' => DonationSetting::get('cost_other', '5'),
            'thank_you_text' => DonationSetting::get('thank_you_text', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('monthly_goal')
                            ->label('Monthly Goal (€)')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                        Textarea::make('donation_message')
                            ->label('Donation Page Message')
                            ->rows(3)
                            ->helperText('Shown on the donate page'),
                        Textarea::make('thank_you_text')
                            ->label('Thank You Message')
                            ->rows(2)
                            ->helperText('Shown after successful donation'),
                    ]),

                Section::make('Monthly Costs (shown on donate page)')
                    ->schema([
                        TextInput::make('cost_servers')->label('Server Hosting (€)')->numeric()->prefix('€'),
                        TextInput::make('cost_storage')->label('File Storage S3 (€)')->numeric()->prefix('€'),
                        TextInput::make('cost_domain')->label('Domain & SSL (€)')->numeric()->prefix('€'),
                        TextInput::make('cost_other')->label('Other Costs (€)')->numeric()->prefix('€'),
                    ])->columns(2),

                Section::make('PayPal')
                    ->schema([
                        Toggle::make('paypal_enabled')->label('PayPal Enabled'),
                        TextInput::make('paypal_email')
                            ->label('PayPal Business E-Mail')
                            ->email()
                            ->helperText('Your PayPal e-mail for receiving donations'),
                    ]),

                Section::make('Notifications')
                    ->schema([
                        TextInput::make('notification_email')
                            ->label('E-Mail Notification')
                            ->email()
                            ->helperText('Receive e-mail for every donation'),
                        TextInput::make('discord_webhook_url')
                            ->label('Discord Webhook URL')
                            ->url()
                            ->helperText('Webhook for donation announcements in Discord'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (is_bool($value)) $value = $value ? '1' : '0';
            DonationSetting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved!')
            ->success()
            ->send();
    }
}
