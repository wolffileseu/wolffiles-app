<?php

namespace App\Filament\Resources;

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

    protected static ?string $navigationIcon = "heroicon-o-chat-bubble-left-right";

    protected static ?string $navigationGroup = "PM System";

    protected static ?string $navigationLabel = "Conversations";

    protected static ?string $modelLabel = "PM Conversation";

    protected static ?string $pluralModelLabel = "PM Conversations";

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can("view_any_pm_conversation") ?? false;
    }

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
                Tables\Columns\TextColumn::make("id")
                    ->label("ID")
                    ->sortable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("type")
                    ->label("Type")
                    ->badge()
                    ->color(fn (string $state): string => $state === "group" ? "info" : "gray")
                    ->size("sm"),

                Tables\Columns\TextColumn::make("subject")
                    ->label("Subject")
                    ->limit(40)
                    ->placeholder("(no subject)")
                    ->size("sm"),

                Tables\Columns\TextColumn::make("participants_summary")
                    ->label("Participants")
                    ->getStateUsing(function ($record) {
                        return $record->participants
                            ->take(4)
                            ->map(fn ($p) => $p->user?->name ?? "(deleted)")
                            ->implode(", ");
                    })
                    ->wrap()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("message_count")
                    ->label("Msgs")
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->size("sm"),

                Tables\Columns\IconColumn::make("locked")
                    ->label("Locked")
                    ->boolean()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("last_message_at")
                    ->label("Last activity")
                    ->dateTime("Y-m-d H:i")
                    ->sortable()
                    ->placeholder("never")
                    ->size("sm"),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Created")
                    ->date("Y-m-d")
                    ->sortable()
                    ->size("sm")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("type")
                    ->options([
                        "direct" => "Direct (1:1)",
                        "group"  => "Group",
                    ]),

                Tables\Filters\TernaryFilter::make("locked")
                    ->label("Lock state")
                    ->trueLabel("Locked only")
                    ->falseLabel("Unlocked only"),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label("View"),
            ])
            ->bulkActions([])
            ->defaultSort("last_message_at", "desc");
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist->schema([
            \Filament\Infolists\Components\Section::make("Conversation #" . $infolist->getRecord()?->id)
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make("type")->badge()->color(fn ($state) => $state === "group" ? "info" : "gray"),
                    \Filament\Infolists\Components\TextEntry::make("subject")->placeholder("(no subject)"),
                    \Filament\Infolists\Components\TextEntry::make("creator.name")->label("Created by"),
                    \Filament\Infolists\Components\TextEntry::make("created_at")->dateTime(),
                    \Filament\Infolists\Components\TextEntry::make("last_message_at")->dateTime()->placeholder("never"),
                    \Filament\Infolists\Components\IconEntry::make("locked")->boolean(),
                    \Filament\Infolists\Components\TextEntry::make("message_count")->numeric(),
                ])
                ->columns(3),

            \Filament\Infolists\Components\Section::make("Participants")
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make("participants")
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make("user.name")->label("User")->weight("bold"),
                            \Filament\Infolists\Components\TextEntry::make("joined_at")->dateTime("Y-m-d H:i"),
                            \Filament\Infolists\Components\TextEntry::make("left_at")->dateTime("Y-m-d H:i")->placeholder("active"),
                            \Filament\Infolists\Components\TextEntry::make("last_read_at")->dateTime("Y-m-d H:i")->placeholder("never"),
                        ])
                        ->columns(4),
                ])
                ->collapsed(),

            \Filament\Infolists\Components\Section::make("Messages")
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make("messages")
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make("sender.name")
                                ->label("From")
                                ->weight("bold")
                                ->columnSpan(1),
                            \Filament\Infolists\Components\TextEntry::make("created_at")
                                ->label("Sent")
                                ->dateTime("Y-m-d H:i:s")
                                ->columnSpan(1),
                            \Filament\Infolists\Components\TextEntry::make("ip_address")
                                ->label("IP")
                                ->copyable()
                                ->columnSpan(1),
                            \Filament\Infolists\Components\TextEntry::make("body")
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
            "index" => Pages\ListPmConversations::route("/"),
            "view"  => Pages\ViewPmConversation::route("/{record}"),
        ];
    }
}
