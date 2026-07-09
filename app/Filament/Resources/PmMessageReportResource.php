<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Filament\Resources\PmMessageReportResource\Pages\ListPmMessageReports;
use App\Filament\Resources\PmMessageReportResource\Pages\EditPmMessageReport;
use App\Filament\Resources\PmMessageReportResource\Pages;
use App\Models\Pm\PmAdminAccessLog;
use App\Models\Pm\PmMessageReport;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PmMessageReportResource extends Resource
{
    protected static ?string $model = PmMessageReport::class;

    protected static string | \BackedEnum | null $navigationIcon = "heroicon-o-flag";

    protected static string | \UnitEnum | null $navigationGroup = "PM System";

    protected static ?string $navigationLabel = "Reports";

    protected static ?string $modelLabel = "PM Report";

    protected static ?string $pluralModelLabel = "PM Reports";

    protected static ?int $navigationSort = 1;


    public static function getNavigationBadge(): ?string
    {
        $count = PmMessageReport::whereIn("status", ["open", "reviewing"])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return "danger";
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Report")
                ->schema([
                    Placeholder::make("reporter")
                        ->label("Reported by")
                        ->content(fn ($record) => $record?->reporter?->name ?? "(unknown)"),

                    Placeholder::make("created_at")
                        ->label("Reported at")
                        ->content(fn ($record) => $record?->created_at?->format("Y-m-d H:i:s") ?? "-"),

                    TextInput::make("reason_code")
                        ->label("Reason code")
                        ->disabled(),

                    Textarea::make("reason_text")
                        ->label("Reporter\'s description")
                        ->disabled()
                        ->rows(3),

                    Placeholder::make("message_body")
                        ->label("Reported message body")
                        ->content(function ($record) {
                            if (! $record || ! $record->message) {
                                return "(message deleted)";
                            }
                            if ($record->message->isPurged()) {
                                return "(body purged by retention)";
                            }
                            return $record->message->body;
                        }),

                    Placeholder::make("message_sender")
                        ->label("Sender of reported message")
                        ->content(fn ($record) => $record?->message?->sender?->name ?? "(unknown)"),
                ])
                ->columns(2),

            Section::make("Resolution")
                ->schema([
                    Select::make("status")
                        ->options([
                            "open"      => "Open",
                            "reviewing" => "Reviewing",
                            "resolved"  => "Resolved",
                            "dismissed" => "Dismissed",
                        ])
                        ->required(),

                    Textarea::make("resolution_note")
                        ->label("Resolution note (internal)")
                        ->rows(3)
                        ->helperText("Visible to other mods/admins. Not shown to users."),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("created_at")
                    ->label("Reported")
                    ->dateTime("Y-m-d H:i")
                    ->sortable()
                    ->size("sm"),

                TextColumn::make("reporter.name")
                    ->label("Reporter")
                    ->searchable()
                    ->sortable()
                    ->size("sm"),

                TextColumn::make("message.sender.name")
                    ->label("Reported user")
                    ->searchable()
                    ->size("sm")
                    ->color("warning"),

                TextColumn::make("reason_code")
                    ->label("Reason")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "spam"       => "warning",
                        "harassment" => "danger",
                        "illegal"    => "danger",
                        "threat"     => "danger",
                        default      => "gray",
                    })
                    ->size("sm"),

                TextColumn::make("reason_text")
                    ->label("Description")
                    ->limit(60)
                    ->wrap()
                    ->size("sm"),

                TextColumn::make("status")
                    ->label("Status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "open"      => "danger",
                        "reviewing" => "warning",
                        "resolved"  => "success",
                        "dismissed" => "gray",
                    })
                    ->size("sm"),

                TextColumn::make("resolver.name")
                    ->label("Resolved by")
                    ->size("sm")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "open"      => "Open",
                        "reviewing" => "Reviewing",
                        "resolved"  => "Resolved",
                        "dismissed" => "Dismissed",
                    ])
                    ->default("open"),

                SelectFilter::make("reason_code")
                    ->options([
                        "spam"       => "Spam",
                        "harassment" => "Harassment",
                        "illegal"    => "Illegal content",
                        "threat"     => "Threat",
                        "other"      => "Other",
                    ]),
            ])
            ->recordActions([
                Action::make("view_conversation")
                    ->label("View conversation")
                    ->icon("heroicon-o-eye")
                    ->color("warning")
                    ->visible(fn ($record): bool => $record->conversation_id !== null
                        && auth()->user()?->can("view_any_pm::conversation"))
                    ->url(fn ($record): string => route("filament.admin.resources.pm-conversations.view", $record->conversation_id))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->label("Resolve"),
            ])
            ->toolbarActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => ListPmMessageReports::route("/"),
            "edit"  => EditPmMessageReport::route("/{record}/edit"),
        ];
    }
}
