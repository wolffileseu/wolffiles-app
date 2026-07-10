<?php

namespace App\Filament\Resources\PmConversationResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\PmConversationResource;
use App\Models\Pm\PmAdminAccessLog;
use App\Models\Pm\PmEvidenceSnapshot;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPmConversation extends ViewRecord
{
    protected static string $resource = PmConversationResource::class;

    /**
     * Audit-log every conversation view (DSGVO requirement).
     * Loaded ONCE per page-load, even if user reloads infolist tabs.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $admin = Auth::user();
        if ($admin && $this->record) {
            PmAdminAccessLog::create([
                "admin_id"        => $admin->id,
                "conversation_id" => $this->record->id,
                "message_id"      => null,
                "action"          => "view_conversation",
                "reason"          => "Viewing conversation #" . $this->record->id . " from admin panel",
                "admin_ip"        => request()->ip(),
                "user_agent"      => mb_substr((string) request()->userAgent(), 0, 500),
                "created_at"      => now(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Lock / Unlock toggle
            Action::make("toggle_lock")
                ->label(fn () => $this->record->locked ? "Unlock conversation" : "Lock conversation")
                ->icon(fn () => $this->record->locked ? "heroicon-o-lock-open" : "heroicon-o-lock-closed")
                ->color(fn () => $this->record->locked ? "success" : "warning")
                ->visible(fn () => Auth::user()?->can("lock_pm_conversation"))
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->locked ? "Unlock conversation?" : "Lock conversation?")
                ->modalDescription(fn () => $this->record->locked
                    ? "Participants will be able to send new messages again."
                    : "Participants will not be able to send new messages until you unlock it.")
                ->schema([
                    Textarea::make("reason")
                        ->label("Reason (audit-logged)")
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    $newState = ! $this->record->locked;
                    $this->record->update(["locked" => $newState]);

                    PmAdminAccessLog::create([
                        "admin_id"        => Auth::id(),
                        "conversation_id" => $this->record->id,
                        "action"          => $newState ? "lock" : "unlock",
                        "reason"          => $data["reason"],
                        "admin_ip"        => request()->ip(),
                        "user_agent"      => mb_substr((string) request()->userAgent(), 0, 500),
                        "created_at"      => now(),
                    ]);

                    Notification::make()
                        ->title($newState ? "Conversation locked" : "Conversation unlocked")
                        ->success()
                        ->send();
                }),

            // Create evidence snapshot
            Action::make("create_snapshot")
                ->label("Create evidence snapshot")
                ->icon("heroicon-o-camera")
                ->color("danger")
                ->visible(fn () => Auth::user()?->can("create_pm::evidence::snapshot"))
                ->requiresConfirmation()
                ->modalHeading("Create evidence snapshot")
                ->modalDescription("This freezes the current state of the conversation as immutable evidence (write-once). Use for legal/moderation cases. The conversation will be exempt from retention purge as long as a snapshot exists.")
                ->schema([
                    Textarea::make("reason")
                        ->label("Reason (mandatory, audit-logged)")
                        ->required()
                        ->maxLength(500),
                    TextInput::make("related_report_id")
                        ->label("Related report ID (optional)")
                        ->numeric()
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    // Build the snapshot: full conversation as JSON
                    $snapshotData = [
                        "snapshot_taken_at" => now()->toIso8601String(),
                        "snapshot_taken_by" => Auth::user()->name,
                        "conversation"     => [
                            "id"               => $this->record->id,
                            "type"             => $this->record->type,
                            "subject"          => $this->record->subject,
                            "created_at"       => $this->record->created_at?->toIso8601String(),
                            "last_message_at"  => $this->record->last_message_at?->toIso8601String(),
                            "locked"           => (bool) $this->record->locked,
                            "message_count"    => $this->record->message_count,
                        ],
                        "participants" => $this->record->participants->map(fn ($p) => [
                            "user_id"      => $p->user_id,
                            "user_name"    => $p->user?->name,
                            "joined_at"    => $p->joined_at?->toIso8601String(),
                            "left_at"      => $p->left_at?->toIso8601String(),
                            "last_read_at" => $p->last_read_at?->toIso8601String(),
                        ])->values()->all(),
                        "messages" => $this->record->messages->map(fn ($m) => [
                            "id"             => $m->id,
                            "sender_id"      => $m->sender_id,
                            "sender_name"    => $m->sender?->name,
                            "body"           => $m->body,
                            "body_format"    => $m->body_format,
                            "body_purged_at" => $m->body_purged_at?->toIso8601String(),
                            "edited_at"      => $m->edited_at?->toIso8601String(),
                            "ip_address"     => $m->ip_address,
                            "user_agent"     => $m->user_agent,
                            "created_at"     => $m->created_at?->toIso8601String(),
                        ])->values()->all(),
                    ];

                    $jsonData = json_encode($snapshotData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $hash = PmEvidenceSnapshot::hashData($jsonData);

                    $snapshot = PmEvidenceSnapshot::create([
                        "conversation_id"   => $this->record->id,
                        "snapshot_data"     => $jsonData,
                        "snapshot_hash"     => $hash,
                        "reason"            => $data["reason"],
                        "related_report_id" => $data["related_report_id"] ?? null,
                        "created_by"        => Auth::id(),
                    ]);

                    PmAdminAccessLog::create([
                        "admin_id"        => Auth::id(),
                        "conversation_id" => $this->record->id,
                        "action"          => "create_snapshot",
                        "reason"          => "Snapshot #" . $snapshot->id . ": " . $data["reason"],
                        "admin_ip"        => request()->ip(),
                        "user_agent"      => mb_substr((string) request()->userAgent(), 0, 500),
                        "created_at"      => now(),
                    ]);

                    Notification::make()
                        ->title("Snapshot #" . $snapshot->id . " created")
                        ->body("SHA256: " . substr($hash, 0, 16) . "...")
                        ->success()
                        ->send();
                }),
        ];
    }
}
