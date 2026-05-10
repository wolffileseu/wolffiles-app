<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PmAdminAccessLogResource\Pages;
use App\Models\Pm\PmAdminAccessLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PmAdminAccessLogResource extends Resource
{
    protected static ?string $model = PmAdminAccessLog::class;

    protected static ?string $navigationIcon = "heroicon-o-document-magnifying-glass";

    protected static ?string $navigationGroup = "PM System";

    protected static ?string $navigationLabel = "Audit Log";

    protected static ?string $modelLabel = "Audit Entry";

    protected static ?string $pluralModelLabel = "Audit Log";

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->can("view_any_pm_admin_access_log") ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("created_at")
                    ->label("When")
                    ->dateTime("Y-m-d H:i:s")
                    ->sortable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("admin.name")
                    ->label("Admin")
                    ->searchable()
                    ->sortable()
                    ->size("sm")
                    ->color("warning"),

                Tables\Columns\TextColumn::make("action")
                    ->label("Action")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "view_conversation"  => "info",
                        "view_message"       => "info",
                        "view_inbox"         => "gray",
                        "create_snapshot"    => "danger",
                        "lock"               => "warning",
                        "unlock"             => "success",
                        "export"             => "danger",
                        "resolve_report"     => "success",
                        default              => "gray",
                    })
                    ->size("sm"),

                Tables\Columns\TextColumn::make("conversation_id")
                    ->label("Conv")
                    ->numeric()
                    ->sortable()
                    ->size("sm")
                    ->placeholder("-"),

                Tables\Columns\TextColumn::make("message_id")
                    ->label("Msg")
                    ->numeric()
                    ->sortable()
                    ->size("sm")
                    ->placeholder("-")
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make("reason")
                    ->label("Reason")
                    ->limit(80)
                    ->wrap()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("admin_ip")
                    ->label("IP")
                    ->size("sm")
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("action")
                    ->options([
                        "view_inbox"        => "View inbox",
                        "view_conversation" => "View conversation",
                        "view_message"      => "View message",
                        "create_snapshot"   => "Create snapshot",
                        "lock"              => "Lock",
                        "unlock"            => "Unlock",
                        "export"            => "Export",
                        "resolve_report"    => "Resolve report",
                    ]),

                Tables\Filters\SelectFilter::make("admin_id")
                    ->label("Admin")
                    ->relationship("admin", "name"),

                Tables\Filters\Filter::make("conversation_id")
                    ->form([
                        \Filament\Forms\Components\TextInput::make("conversation_id")
                            ->numeric()
                            ->label("Conversation ID"),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data["conversation_id"] ?? null,
                            fn ($q, $id) => $q->where("conversation_id", $id))),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListPmAdminAccessLogs::route("/"),
        ];
    }
}
