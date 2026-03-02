<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaChannelResource\Pages;
use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SocialMediaChannelResource extends Resource
{
    protected static ?string $model = SocialMediaChannel::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Social Media Broadcast';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 50;
    protected static ?string $modelLabel = 'Social Media Channel';
    protected static ?string $pluralModelLabel = 'Social Media Channels';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Channel Configuration')
                ->description('Configure a social media channel for automatic notifications.')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Channel Name')
                            ->required()
                            ->placeholder('e.g. Discord #announcements, Reddit r/enemyterritory')
                            ->maxLength(255),

                        Forms\Components\Select::make('provider')
                            ->label('Platform')
                            ->options(SocialMediaChannel::PROVIDERS)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('config', [])),
                    ]),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Disable to temporarily stop posting to this channel.'),

                    Forms\Components\CheckboxList::make('enabled_events')
                        ->label('Enabled Events')
                        ->options(SocialMediaChannel::EVENTS)
                        ->columns(3)
                        ->required()
                        ->helperText('Select which events should be posted to this channel.'),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers are processed first.'),
                ]),

            // Dynamic provider config section
            Forms\Components\Section::make('Provider Settings')
                ->description('API credentials and settings for the selected platform.')
                ->schema(fn (Forms\Get $get) => static::getProviderConfigSchema($get('provider')))
                ->visible(fn (Forms\Get $get) => !empty($get('provider'))),

            // Custom message templates
            Forms\Components\Section::make('Custom Message Templates')
                ->description('Optional: Customize the message format. Use {title}, {description}, {url}, {category}, {uploader}, {author}, {amount}, {donor} as placeholders.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('message_template_file')
                        ->label('File Approved Template')
                        ->rows(3)
                        ->placeholder('📁 New file: {title} — Category: {category} — {url}')
                        ->helperText('Placeholders: {title}, {description}, {url}, {category}, {file_size}, {uploader}'),

                    Forms\Components\Textarea::make('message_template_donation')
                        ->label('Donation Template')
                        ->rows(3)
                        ->placeholder('💝 Thank you {donor} for €{amount}!')
                        ->helperText('Placeholders: {amount}, {donor}, {message}'),

                    Forms\Components\Textarea::make('message_template_motw')
                            ->label('Map of the Week Template')
                            ->rows(3),
                        Forms\Components\Textarea::make('message_template_news')
                        ->label('Map of the Week Template')
                        ->rows(3)
                        ->placeholder('🏆 Map of the Week: {title} by {author}')
                        ->helperText('Placeholders: {title}, {description}, {url}, {author}, {download_count}, {rating}'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SocialMediaChannel::PROVIDERS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'discord' => 'info',
                        'reddit' => 'warning',
                        'twitter' => 'gray',
                        'facebook' => 'primary',
                        default => 'secondary',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('enabled_events')
                    ->label('Events')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return collect($state)->map(fn ($e) => SocialMediaChannel::EVENTS[$e] ?? $e)->implode(', ');
                        }
                        return '-';
                    })
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('last_posted_at')
                    ->label('Last Posted')
                    ->since()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('last_error')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state) => $state ? '❌ Error' : '✅ OK')
                    ->tooltip(fn (?string $state) => $state)
                    ->color(fn (?string $state) => $state ? 'danger' : 'success'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->options(SocialMediaChannel::PROVIDERS),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Test Connection')
                    ->modalDescription('This will send a test message to the channel.')
                    ->action(function (SocialMediaChannel $record) {
                        $service = app(SocialMediaService::class);
                        $result = $service->testChannel($record);

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Connection Successful')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Connection Failed')
                                ->body($result['message'])
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialMediaChannels::route('/'),
            'create' => Pages\CreateSocialMediaChannel::route('/create'),
            'edit' => Pages\EditSocialMediaChannel::route('/{record}/edit'),
        ];
    }

    /**
     * Generate dynamic config form fields based on provider type.
     */
    protected static function getProviderConfigSchema(?string $provider): array
    {
        if (!$provider) return [];

        $service = app(SocialMediaService::class);
        $fields = $service->getProviderConfigFields($provider);

        return collect($fields)->map(function ($field, $key) {
            $component = match ($field['type'] ?? 'text') {
                'password' => Forms\Components\TextInput::make("config.{$key}")
                    ->label($field['label'])
                    ->password()
                    ->revealable(),
                'url' => Forms\Components\TextInput::make("config.{$key}")
                    ->label($field['label'])
                    ->url(),
                'textarea' => Forms\Components\Textarea::make("config.{$key}")
                    ->label($field['label']),
                default => Forms\Components\TextInput::make("config.{$key}")
                    ->label($field['label']),
            };

            if ($field['required'] ?? false) {
                $component = $component->required();
            }

            if (isset($field['placeholder'])) {
                $component = $component->placeholder($field['placeholder']);
            }

            if (isset($field['help'])) {
                $component = $component->helperText($field['help']);
            }

            if (isset($field['default'])) {
                $component = $component->default($field['default']);
            }

            return $component;
        })->values()->toArray();
    }
}
