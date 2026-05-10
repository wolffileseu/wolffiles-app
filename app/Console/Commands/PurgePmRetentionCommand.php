<?php

namespace App\Console\Commands;

use App\Models\Pm\PmAttachment;
use App\Models\Pm\PmConversation;
use App\Models\Pm\PmMessage;
use App\Models\Pm\PmMessageReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PM Retention purge.
 *
 * For messages older than configured retention_body_days (default 180):
 *   - Sets body to NULL and body_purged_at to now
 *   - Deletes attachment files from storage and sets purged_at
 *
 * EXCEPTIONS — conversations are SKIPPED if any of these apply:
 *   - There is an open or reviewing report on any message in the conversation
 *   - There is an evidence_snapshot tied to the conversation
 *
 * Metadata (sender_id, ip_address, user_agent, created_at) is preserved
 * regardless, until a separate metadata-retention job (not yet built)
 * purges those after retention_metadata_days.
 *
 * Idempotent: messages already purged are skipped.
 *
 * Use --dry-run to see what would happen without writing.
 */
class PurgePmRetentionCommand extends Command
{
    protected $signature = "pm:purge-retention
                            {--dry-run : Show what would be purged without writing}
                            {--limit=10000 : Maximum messages to process per run}";

    protected $description = "Purge PM message bodies and attachments older than retention period (DSGVO/GDPR)";

    public function handle(): int
    {
        $dryRun = (bool) $this->option("dry-run");
        $limit  = (int) $this->option("limit");

        $retentionDays = (int) config("pm.retention_body_days", 180);
        $cutoff = Carbon::now()->subDays($retentionDays);

        $this->info("PM retention purge — cutoff: {$cutoff->toDateTimeString()} ({$retentionDays} days)");
        if ($dryRun) {
            $this->warn("DRY RUN — no changes will be written.");
        }

        // -----------------------------------------------------------------
        // Build set of conversation IDs to SKIP (open reports OR snapshots)
        // -----------------------------------------------------------------
        $skipConvIds = collect();

        $reportConvIds = PmMessageReport::query()
            ->whereIn("status", ["open", "reviewing"])
            ->pluck("conversation_id")
            ->unique();

        $snapshotConvIds = DB::table("pm_evidence_snapshots")
            ->pluck("conversation_id")
            ->unique();

        $skipConvIds = $reportConvIds->merge($snapshotConvIds)->unique()->values();

        $this->info("Conversations to skip (active reports / snapshots): {$skipConvIds->count()}");

        // -----------------------------------------------------------------
        // Find candidate messages
        // -----------------------------------------------------------------
        $candidates = PmMessage::query()
            ->whereNull("body_purged_at")
            ->whereNotNull("body")
            ->where("created_at", "<", $cutoff)
            ->when($skipConvIds->isNotEmpty(), function ($q) use ($skipConvIds) {
                $q->whereNotIn("conversation_id", $skipConvIds);
            })
            ->orderBy("id")
            ->limit($limit);

        $totalCandidates = (clone $candidates)->count();
        $this->info("Candidate messages: {$totalCandidates}");

        if ($totalCandidates === 0) {
            $this->info("Nothing to purge.");
            return Command::SUCCESS;
        }

        // -----------------------------------------------------------------
        // Process in chunks
        // -----------------------------------------------------------------
        $purgedMessages = 0;
        $purgedAttachments = 0;
        $errors = 0;

        $candidates->chunkById(500, function ($messages) use (
            $dryRun,
            &$purgedMessages,
            &$purgedAttachments,
            &$errors,
        ) {
            foreach ($messages as $message) {
                try {
                    // Purge attachments first (S3 cleanup)
                    foreach ($message->attachments()->whereNull("purged_at")->get() as $att) {
                        try {
                            if (! $dryRun) {
                                Storage::disk($att->storage_disk)->delete($att->storage_path);
                                if ($att->thumbnail_path) {
                                    Storage::disk($att->storage_disk)->delete($att->thumbnail_path);
                                }
                                $att->update(["purged_at" => now()]);
                            }
                            $purgedAttachments++;
                        } catch (\Throwable $e) {
                            // Do not fail the whole run on a single attachment error
                            Log::warning("PM retention: failed to purge attachment", [
                                "attachment_id" => $att->id,
                                "error"         => $e->getMessage(),
                            ]);
                            $errors++;
                        }
                    }

                    // Null the body
                    if (! $dryRun) {
                        $message->update([
                            "body"           => null,
                            "body_purged_at" => now(),
                        ]);
                    }
                    $purgedMessages++;
                } catch (\Throwable $e) {
                    Log::error("PM retention: failed to purge message", [
                        "message_id" => $message->id,
                        "error"      => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }
        });

        // -----------------------------------------------------------------
        // Summary
        // -----------------------------------------------------------------
        $this->info("");
        $this->info(($dryRun ? "[DRY RUN] " : "") . "Summary:");
        $this->table(
            ["Metric", "Count"],
            [
                ["Messages purged",      $purgedMessages],
                ["Attachments purged",   $purgedAttachments],
                ["Skipped conversations", $skipConvIds->count()],
                ["Errors",               $errors],
            ]
        );

        Log::channel(config("logging.default"))->info("PM retention completed", [
            "dry_run"            => $dryRun,
            "purged_messages"    => $purgedMessages,
            "purged_attachments" => $purgedAttachments,
            "skipped_convs"      => $skipConvIds->count(),
            "errors"             => $errors,
            "cutoff"             => $cutoff->toIso8601String(),
        ]);

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
