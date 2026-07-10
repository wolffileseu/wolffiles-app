<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\PmEvidenceSnapshotResource\Pages\ListPmEvidenceSnapshots;
use App\Filament\Resources\PmEvidenceSnapshotResource\Pages\ViewPmEvidenceSnapshot;
use App\Filament\Resources\PmEvidenceSnapshotResource\Pages;
use App\Models\Pm\PmEvidenceSnapshot;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PmEvidenceSnapshotResource extends Resource
{
    protected static ?string $model = PmEvidenceSnapshot::class;

    protected static string | \BackedEnum | null $navigationIcon = "heroicon-o-camera";

    protected static string | \UnitEnum | null $navigationGroup = "PM System";

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
                TextColumn::make("id")
                    ->label("ID")
                    ->sortable()
                    ->size("sm"),

                TextColumn::make("created_at")
                    ->label("Created")
                    ->dateTime("Y-m-d H:i:s")
                    ->sortable()
                    ->size("sm"),

                TextColumn::make("conversation_id")
                    ->label("Conv")
                    ->numeric()
                    ->sortable()
                    ->size("sm")
                    ->url(fn ($record) => $record->conversation_id
                        ? route("filament.admin.resources.pm-conversations.view", $record->conversation_id)
                        : null)
                    ->color("warning"),

                TextColumn::make("creator.name")
                    ->label("Created by")
                    ->searchable()
                    ->size("sm"),

                TextColumn::make("reason")
                    ->label("Reason")
                    ->limit(60)
                    ->wrap()
                    ->size("sm"),

                TextColumn::make("related_report_id")
                    ->label("Report")
                    ->numeric()
                    ->size("sm")
                    ->placeholder("-"),

                TextColumn::make("snapshot_hash")
                    ->label("SHA256")
                    ->size("sm")
                    ->copyable()
                    ->limit(16)
                    ->tooltip(fn ($record) => $record->snapshot_hash),

                TextColumn::make("integrity")
                    ->label("Integrity")
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->verifyIntegrity() ? "VERIFIED" : "TAMPERED")
                    ->color(fn (string $state) => $state === "VERIFIED" ? "success" : "danger")
                    ->size("sm"),
            ])
            ->filters([
                Filter::make("conversation_id")
                    ->schema([
                        TextInput::make("conversation_id")
                            ->numeric()
                            ->label("Conversation ID"),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data["conversation_id"] ?? null,
                            fn ($q, $id) => $q->where("conversation_id", $id))),

                SelectFilter::make("created_by")
                    ->label("Created by")
                    ->relationship("creator", "name"),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label("View"),

                Action::make("download_json")
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
            ->toolbarActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Snapshot metadata")
                ->schema([
                    TextEntry::make("id")->label("ID"),
                    TextEntry::make("created_at")->dateTime(),
                    TextEntry::make("creator.name")->label("Created by"),
                    TextEntry::make("conversation_id")
                        ->label("Conversation")
                        ->url(fn ($record) => $record->conversation_id
                            ? route("filament.admin.resources.pm-conversations.view", $record->conversation_id)
                            : null)
                        ->color("warning"),
                    TextEntry::make("related_report_id")->label("Related report")->placeholder("-"),
                    TextEntry::make("reason")->columnSpanFull(),
                    TextEntry::make("snapshot_hash")
                        ->label("SHA256 hash")
                        ->copyable()
                        ->columnSpanFull(),
                    TextEntry::make("integrity_status")
                        ->label("Integrity")
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->verifyIntegrity() ? "VERIFIED" : "TAMPERED")
                        ->color(fn (string $state) => $state === "VERIFIED" ? "success" : "danger"),
                ])
                ->columns(3),

            Section::make("Snapshot data (JSON)")
                ->schema([
                    TextEntry::make("snapshot_data")
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
            "index" => ListPmEvidenceSnapshots::route("/"),
            "view"  => ViewPmEvidenceSnapshot::route("/{record}"),
        ];
    }
}
