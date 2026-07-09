<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PmEvidenceSnapshotResource\Pages;
use App\Models\Pm\PmEvidenceSnapshot;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PmEvidenceSnapshotResource extends Resource
{
    protected static ?string $model = PmEvidenceSnapshot::class;

    protected static ?string $navigationIcon = "heroicon-o-camera";

    protected static ?string $navigationGroup = "PM System";

    protected static ?string $navigationLabel = "Snapshots";

    protected static ?string $modelLabel = "Evidence Snapshot";

    protected static ?string $pluralModelLabel = "Evidence Snapshots";

    protected static ?int $navigationSort = 3;


    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("id")
                    ->label("ID")
                    ->sortable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Created")
                    ->dateTime("Y-m-d H:i:s")
                    ->sortable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("conversation_id")
                    ->label("Conv")
                    ->numeric()
                    ->sortable()
                    ->size("sm")
                    ->url(fn ($record) => $record->conversation_id
                        ? route("filament.admin.resources.pm-conversations.view", $record->conversation_id)
                        : null)
                    ->color("warning"),

                Tables\Columns\TextColumn::make("creator.name")
                    ->label("Created by")
                    ->searchable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("reason")
                    ->label("Reason")
                    ->limit(60)
                    ->wrap()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("related_report_id")
                    ->label("Report")
                    ->numeric()
                    ->size("sm")
                    ->placeholder("-"),

                Tables\Columns\TextColumn::make("snapshot_hash")
                    ->label("SHA256")
                    ->size("sm")
                    ->copyable()
                    ->limit(16)
                    ->tooltip(fn ($record) => $record->snapshot_hash),

                Tables\Columns\TextColumn::make("integrity")
                    ->label("Integrity")
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->verifyIntegrity() ? "VERIFIED" : "TAMPERED")
                    ->color(fn (string $state) => $state === "VERIFIED" ? "success" : "danger")
                    ->size("sm"),
            ])
            ->filters([
                Tables\Filters\Filter::make("conversation_id")
                    ->form([
                        \Filament\Forms\Components\TextInput::make("conversation_id")
                            ->numeric()
                            ->label("Conversation ID"),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data["conversation_id"] ?? null,
                            fn ($q, $id) => $q->where("conversation_id", $id))),

                Tables\Filters\SelectFilter::make("created_by")
                    ->label("Created by")
                    ->relationship("creator", "name"),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label("View"),

                Tables\Actions\Action::make("download_json")
                    ->label("Download JSON")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("info")
                    ->action(function ($record) {
                        return response()->streamDownload(
                            fn () => print($record->snapshot_data),
                            "pm-snapshot-{$record->id}-conv{$record->conversation_id}.json",
                            ["Content-Type" => "application/json"]
                        );
                    }),
            ])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist->schema([
            \Filament\Infolists\Components\Section::make("Snapshot metadata")
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make("id")->label("ID"),
                    \Filament\Infolists\Components\TextEntry::make("created_at")->dateTime(),
                    \Filament\Infolists\Components\TextEntry::make("creator.name")->label("Created by"),
                    \Filament\Infolists\Components\TextEntry::make("conversation_id")
                        ->label("Conversation")
                        ->url(fn ($record) => $record->conversation_id
                            ? route("filament.admin.resources.pm-conversations.view", $record->conversation_id)
                            : null)
                        ->color("warning"),
                    \Filament\Infolists\Components\TextEntry::make("related_report_id")->label("Related report")->placeholder("-"),
                    \Filament\Infolists\Components\TextEntry::make("reason")->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make("snapshot_hash")
                        ->label("SHA256 hash")
                        ->copyable()
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make("integrity_status")
                        ->label("Integrity")
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->verifyIntegrity() ? "VERIFIED" : "TAMPERED")
                        ->color(fn (string $state) => $state === "VERIFIED" ? "success" : "danger"),
                ])
                ->columns(3),

            \Filament\Infolists\Components\Section::make("Snapshot data (JSON)")
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make("snapshot_data")
                        ->label("")
                        ->getStateUsing(fn ($record) => $record->snapshot_data)
                        ->columnSpanFull()
                        ->extraAttributes(["style" => "white-space:pre;font-family:monospace;font-size:11px;background:#0a0a0a;padding:12px;border-radius:4px;overflow:auto;max-height:600px;"]),
                ])
                ->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListPmEvidenceSnapshots::route("/"),
            "view"  => Pages\ViewPmEvidenceSnapshot::route("/{record}"),
        ];
    }
}
