<?php

namespace App\Filament\Resources\Support;

use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketSource;
use App\Enums\Support\TicketStatus;
use App\Filament\Resources\Support\TicketResource\Pages\CreateTicket;
use App\Filament\Resources\Support\TicketResource\Pages\EditTicket;
use App\Filament\Resources\Support\TicketResource\Pages\ListTickets;
use App\Filament\Resources\Support\TicketResource\RelationManagers\MessagesRelationManager;
use App\Models\Support\Category;
use App\Models\Support\CategorySubscription;
use App\Models\Support\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lifebuoy';
    protected static string | \UnitEnum | null $navigationGroup = 'Support';
    protected static ?string $navigationLabel = 'Tickets';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')->columns(2)->schema([
                TextInput::make('public_id')->label('ID')->disabled()->dehydrated(false),
                Select::make('category_id')->label('Category')
                    ->options(fn () => Category::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                    ->searchable()->nullable(),
                TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
            ]),

            Section::make('Classification')->columns(4)->schema([
                Select::make('status')->options(TicketStatus::options())->default('new')->required(),
                Select::make('priority')->options(TicketPriority::options())->default('normal')->required(),
                Select::make('source')->options(TicketSource::options())->default('admin')->required(),
                Select::make('assignee_id')->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()->nullable(),
            ]),

            Section::make('Requester')->columns(2)->collapsed()->schema([
                Select::make('user_id')->label('Account')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()->nullable()
                    ->helperText('Leave empty for guest or Discord-only tickets'),
                TextInput::make('guest_name')->maxLength(120),
                TextInput::make('guest_email')->email()->maxLength(190),
                TextInput::make('discord_username')->maxLength(120)->disabled(),
            ]),

            Section::make('Channels')->columns(2)->collapsed()->schema([
                Select::make('reply_channel')->options(TicketSource::options())->default('web')
                    ->helperText('Where replies are delivered by default'),
                TextInput::make('discord_thread_id')->maxLength(32)->disabled()
                    ->helperText('Set automatically when the Discord thread is created'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                TextColumn::make('public_id')->label('ID')
                    ->color('gray')->size('sm')->copyable()->searchable()->sortable(),
                TextColumn::make('subject')->searchable()->limit(55)->weight('medium'),
                TextColumn::make('category.name')->badge()->placeholder('—')->sortable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (TicketStatus $state) => $state->label())
                    ->color(fn (TicketStatus $state) => $state->color()),
                TextColumn::make('priority')->badge()
                    ->formatStateUsing(fn (TicketPriority $state) => $state->label())
                    ->color(fn (TicketPriority $state) => $state->color()),
                TextColumn::make('source')->badge()
                    ->formatStateUsing(fn (TicketSource $state) => $state->label())
                    ->color(fn (TicketSource $state) => $state->color())
                    ->toggleable(),
                TextColumn::make('requester_name')->label('From')->limit(28),
                TextColumn::make('assignee.name')->label('Assignee')->placeholder('—')->toggleable(),
                TextColumn::make('last_activity_at')->label('Activity')->since()->sortable(),
                TextColumn::make('created_at')->date()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(TicketStatus::options()),
                SelectFilter::make('priority')->options(TicketPriority::options()),
                SelectFilter::make('source')->options(TicketSource::options()),
                SelectFilter::make('category_id')->label('Category')
                    ->options(fn () => Category::orderBy('sort_order')->pluck('name', 'id')),
                SelectFilter::make('assignee_id')->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')),
                Filter::make('unassigned')->label('Unassigned only')
                    ->query(fn (Builder $q) => $q->whereNull('assignee_id')),
                Filter::make('open')->label('Open only')
                    ->query(fn (Builder $q) => $q->whereIn('status', ['new', 'open', 'pending', 'on_hold'])),
            ])
            ->recordActions([
                Action::make('assignToMe')
                    ->label('Take')
                    ->icon('heroicon-o-hand-raised')
                    ->color('success')
                    ->visible(fn (Ticket $record) => $record->assignee_id === null)
                    ->action(function (Ticket $record) {
                        $record->forceFill([
                            'assignee_id' => auth()->id(),
                            'status'      => $record->status === TicketStatus::New ? TicketStatus::Open : $record->status,
                        ])->save();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Sichtbarkeit: wer support_view_all hat, sieht alles.
     * Alle anderen sehen nur eigene Tickets und Tickets in abonnierten Kategorien.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        if (! $user || $user->can('support_view_all') || $user->hasRole('super_admin')) {
            return $query;
        }

        $categoryIds = CategorySubscription::query()
            ->where('user_id', $user->id)
            ->pluck('category_id');

        // Ohne Abos und ohne support_view_all bliebe sonst eine leere Liste
        // ohne erkennbaren Grund. Dann lieber alles zeigen -- der Zugriff auf
        // die Resource wird ohnehin schon von der Policy geprueft.
        if ($categoryIds->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($user, $categoryIds) {
            $sub->where('assignee_id', $user->id)
                ->orWhereIn('category_id', $categoryIds);
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', ['new', 'open'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unassigned = static::getEloquentQuery()
            ->whereNull('assignee_id')
            ->whereIn('status', ['new', 'open'])
            ->count();

        return $unassigned > 0 ? 'danger' : 'primary';
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit'   => EditTicket::route('/{record}/edit'),
        ];
    }
}
