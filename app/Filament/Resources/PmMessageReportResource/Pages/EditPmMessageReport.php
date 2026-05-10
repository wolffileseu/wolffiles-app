<?php

namespace App\Filament\Resources\PmMessageReportResource\Pages;

use App\Filament\Resources\PmMessageReportResource;
use App\Models\Pm\PmAdminAccessLog;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPmMessageReport extends EditRecord
{
    protected static string $resource = PmMessageReportResource::class;

    /**
     * Audit-log every view of a report (DSGVO requirement).
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $admin = Auth::user();
        if ($admin && $this->record) {
            PmAdminAccessLog::create([
                "admin_id"        => $admin->id,
                "conversation_id" => $this->record->conversation_id,
                "message_id"      => $this->record->message_id,
                "action"          => "view_message",
                "reason"          => "Viewing message report #" . $this->record->id,
                "admin_ip"        => request()->ip(),
                "user_agent"      => mb_substr((string) request()->userAgent(), 0, 500),
                "created_at"      => now(),
            ]);
        }
    }

    /**
     * On save: set resolved_by and resolved_at if status moved to resolved/dismissed.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data["status"] ?? null, ["resolved", "dismissed"], true)) {
            if (empty($this->record->resolved_at) || $this->record->status !== $data["status"]) {
                $data["resolved_by"] = Auth::id();
                $data["resolved_at"] = now();
            }
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
