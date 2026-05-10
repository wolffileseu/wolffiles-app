<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PmMessageReportResource\Pages;
use App\Models\Pm\PmAdminAccessLog;
use App\Models\Pm\PmMessageReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PmMessageReportResource extends Resource
{
    protected static ?string $model = PmMessageReport::class;

    protected static ?string $navigationIcon = "heroicon-o-flag";

    protected static ?string $navigationGroup = "PM System";

    protected static ?string $navigationLabel = "Reports";

    protected static ?string $modelLabel = "PM Report";

    protected static ?string $pluralModelLabel = "PM Reports";

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->can("view_any_pm_message_report") ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PmMessageReport::whereIn("status", ["open", "reviewing"])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return "danger";
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Report")
                ->schema([
                    Forms\Components\Placeholder::make("reporter")
                        ->label("Reported by")
                        ->content(fn ($record) => $record?->reporter?->name ?? "(unknown)"),

                    Forms\Components\Placeholder::make("created_at")
                        ->label("Reported at")
                        ->content(fn ($record) => $record?->created_at?->format("Y-m-d H:i:s") ?? "-"),

                    Forms\Components\TextInput::make("reason_code")
                        ->label("Reason code")
                        ->disabled(),

                    Forms\Components\Textarea::make("reason_text")
                        ->label("Reporter\'s description")
                        ->disabled()
                        ->rows(3),

                    Forms\Components\Placeholder::make("message_body")
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

                    Forms\Components\Placeholder::make("message_sender")
                        ->label("Sender of reported message")
                        ->content(fn ($record) => $record?->message?->sender?->name ?? "(unknown)"),
                ])
                ->columns(2),

            Forms\Components\Section::make("Resolution")
                ->schema([
                    Forms\Components\Select::make("status")
                        ->options([
                            "open"      => "Open",
                            "reviewing" => "Reviewing",
                            "resolved"  => "Resolved",
                            "dismissed" => "Dismissed",
                        ])
                        ->required(),

                    Forms\Components\Textarea::make("resolution_note")
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
                Tables\Columns\TextColumn::make("created_at")
                    ->label("Reported")
                    ->dateTime("Y-m-d H:i")
                    ->sortable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("reporter.name")
                    ->label("Reporter")
                    ->searchable()
                    ->sortable()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("message.sender.name")
                    ->label("Reported user")
                    ->searchable()
                    ->size("sm")
                    ->color("warning"),

                Tables\Columns\TextColumn::make("reason_code")
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

                Tables\Columns\TextColumn::make("reason_text")
                    ->label("Description")
                    ->limit(60)
                    ->wrap()
                    ->size("sm"),

                Tables\Columns\TextColumn::make("status")
                    ->label("Status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "open"      => "danger",
                        "reviewing" => "warning",
                        "resolved"  => "success",
                        "dismissed" => "gray",
                    })
                    ->size("sm"),

                Tables\Columns\TextColumn::make("resolver.name")
                    ->label("Resolved by")
                    ->size("sm")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("status")
                    ->options([
                        "open"      => "Open",
                        "reviewing" => "Reviewing",
                        "resolved"  => "Resolved",
                        "dismissed" => "Dismissed",
                    ])
                    ->default("open"),

                Tables\Filters\SelectFilter::make("reason_code")
                    ->options([
                        "spam"       => "Spam",
                        "harassment" => "Harassment",
                        "illegal"    => "Illegal content",
                        "threat"     => "Threat",
                        "other"      => "Other",
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make("view_conversation")
                    ->label("View conversation")
                    ->icon("heroicon-o-eye")
                    ->color("warning")
                    ->visible(fn ($record): bool => $record->conversation_id !== null
                        && auth()->user()?->can("view_any_pm_conversation"))
                    ->url(fn ($record): string => route("filament.admin.resources.pm-conversations.view", $record->conversation_id))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->label("Resolve"),
            ])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListPmMessageReports::route("/"),
            "edit"  => Pages\EditPmMessageReport::route("/{record}/edit"),
        ];
    }
}
