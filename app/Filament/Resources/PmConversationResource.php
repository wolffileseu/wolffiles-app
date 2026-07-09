<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\PmConversationResource\Pages\ListPmConversations;
use App\Filament\Resources\PmConversationResource\Pages\ViewPmConversation;
use App\Filament\Resources\PmConversationResource\Pages;
use App\Models\Pm\PmAdminAccessLog;
use App\Models\Pm\PmConversation;
use App\Models\Pm\PmEvidenceSnapshot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PmConversationResource extends Resource
{
    protected static ?string $model = PmConversation::class;

    protected static string | \BackedEnum | null $navigationIcon = "heroicon-o-chat-bubble-left-right";

    protected static string | \UnitEnum | null $navigationGroup = "PM System";

    protected static ?string $navigationLabel = "Conversations";

    protected static ?string $modelLabel = "PM Conversation";

    protected static ?string $pluralModelLabel = "PM Conversations";

    protected static ?int $navigationSort = 2;


    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")
                    ->label("ID")
                    ->sortable()
                    ->size("sm"),

                TextColumn::make("type")
                    ->label("Type")
                    ->badge()
                    ->color(fn (string $state): string => $state === "group" ? "info" : "gray")
                    ->size("sm"),

                TextColumn::make("subject")
                    ->label("Subject")
                    ->limit(40)
                    ->placeholder("(no subject)")
                    ->size("sm"),

                TextColumn::make("participants_summary")
                    ->label("Participants")
                    ->getStateUsing(function ($record) {
                        return $record->participants
                            ->take(4)
                            ->map(fn ($p) => $p->user?->name ?? "(deleted)")
                            ->implode(", ");
                    })
                    ->wrap()
                    ->size("sm"),

                TextColumn::make("message_count")
                    ->label("Msgs")
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->size("sm"),

                IconColumn::make("locked")
                    ->label("Locked")
                    ->boolean()
                    ->size("sm"),

                TextColumn::make("last_message_at")
                    ->label("Last activity")
                    ->dateTime("Y-m-d H:i")
                    ->sortable()
                    ->placeholder("never")
                    ->size("sm"),

                TextColumn::make("created_at")
                    ->label("Created")
                    ->date("Y-m-d")
                    ->sortable()
                    ->size("sm")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make("type")
                    ->options([
                        "direct" => "Direct (1:1)",
                        "group"  => "Group",
                    ]),

                TernaryFilter::make("locked")
                    ->label("Lock state")
                    ->trueLabel("Locked only")
                    ->falseLabel("Unlocked only"),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label("View"),
            ])
            ->toolbarActions([])
            ->defaultSort("last_message_at", "desc");
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Conversation #" . $schema->getRecord()?->id)
                ->schema([
                    TextEntry::make("type")->badge()->color(fn ($state) => $state === "group" ? "info" : "gray"),
                    TextEntry::make("subject")->placeholder("(no subject)"),
                    TextEntry::make("creator.name")->label("Created by"),
                    TextEntry::make("created_at")->dateTime(),
                    TextEntry::make("last_message_at")->dateTime()->placeholder("never"),
                    IconEntry::make("locked")->boolean(),
                    TextEntry::make("message_count")->numeric(),
                ])
                ->columns(3),

            Section::make("Participants")
                ->schema([
                    RepeatableEntry::make("participants")
                        ->schema([
                            TextEntry::make("user.name")->label("User")->weight("bold"),
                            TextEntry::make("joined_at")->dateTime("Y-m-d H:i"),
                            TextEntry::make("left_at")->dateTime("Y-m-d H:i")->placeholder("active"),
                            TextEntry::make("last_read_at")->dateTime("Y-m-d H:i")->placeholder("never"),
                        ])
                        ->columns(4),
                ])
                ->collapsed(),

            Section::make("Messages")
                ->schema([
                    RepeatableEntry::make("messages")
                        ->schema([
                            TextEntry::make("sender.name")
                                ->label("From")
                                ->weight("bold")
                                ->columnSpan(1),
                            TextEntry::make("created_at")
                                ->label("Sent")
                                ->dateTime("Y-m-d H:i:s")
                                ->columnSpan(1),
                            TextEntry::make("ip_address")
                                ->label("IP")
                                ->copyable()
                                ->columnSpan(1),
                            TextEntry::make("body")
                                ->label("Body")
                                ->placeholder(fn ($record) => $record?->body_purged_at ? "(purged by retention on " . $record->body_purged_at->format("Y-m-d") . ")" : "(empty)")
                                ->columnSpanFull()
                                ->html(),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => ListPmConversations::route("/"),
            "view"  => ViewPmConversation::route("/{record}"),
        ];
    }
}
