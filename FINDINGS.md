# Findings — Tracker pipeline analysis

Scope: `app/Jobs/ProcessTrackerEventJob.php`, `app/Services/Tracker/Handlers/ServerLifecycleHandler.php`, `app/Services/Tracker/Handlers/PlayerPresenceHandler.php`, `app/Console/Commands/TrackerPollServers.php`. Cross-referenced against `app/Jobs/Tracker/PollServerJob.php`, `app/Models/Tracker/TrackerServer.php`, `app/Services/Tracker/Handlers/AbstractHandler.php`, and `database/migrations/2026_04_18_075300_create_tracker_server_slots_table.php`.

Note on file paths in CLAUDE.md: the doc references `app/Jobs/Tracker/ProcessTrackerEventJob.php` and `app/Services/Tracker/{ServerLifecycle,PlayerPresence}Handler.php` — actual paths are `app/Jobs/ProcessTrackerEventJob.php` and `app/Services/Tracker/Handlers/*Handler.php`. Worth fixing in CLAUDE.md so future agents (and grep-based tooling) don't trip on it.

Severity scale: **CRITICAL** > **HIGH** > **MEDIUM** > **LOW**.

---

## Bug

### B-1 — `ServerLifecycleHandler` issues 3 sequential UPDATEs on `tracker_servers` per event (deadlock root cause)
**Severity: CRITICAL**
**File:** `app/Services/Tracker/Handlers/ServerLifecycleHandler.php:37-64`

Inside one `DB::transaction()`, every event (including the ~15s `p` keepalive) runs:

1. `UPDATE tracker_servers SET enhanced_last_event_at = ? WHERE id = ?` (lines 47-49)
2. `UPDATE tracker_servers SET enhanced_event_count = enhanced_event_count + 1 WHERE id = ?` (lines 51-53)
3. `UPDATE tracker_servers SET is_enhanced_tracker=1, enhanced_first_seen_at=?, enhanced_source_ip=? WHERE id=? AND is_enhanced_tracker=0` (lines 56-63)

That is three row-locks acquired, held, and released against the same hot row. With 10 `tracker` workers all processing events for the same popular server, concurrent transactions interleave on the same row and InnoDB raises a deadlock — exactly the symptom described in CLAUDE.md ("102k+ failed jobs, almost all deadlocks on `ProcessTrackerEventJob` updating `tracker_servers`"). The `event->update(['server_id' => …])` on `tracker_raw_events` (line 39) widens the lock graph further, since both tables are touched in the same tx.

**Suggested fix:** collapse the three updates into one. Skip the `event_count` increment and the conditional first-packet flip on the keepalive path; coalesce them into a single statement, and for keepalives consider buffering N keepalives in Redis and flushing once per server per N seconds (this is the "Redis buffer + batch flush" architecture CLAUDE.md already calls out). At minimum:

```sql
UPDATE tracker_servers
   SET enhanced_last_event_at  = :received_at,
       enhanced_event_count    = enhanced_event_count + 1,
       is_enhanced_tracker     = 1,
       enhanced_first_seen_at  = COALESCE(enhanced_first_seen_at, :received_at),
       enhanced_source_ip      = COALESCE(enhanced_source_ip, :source_ip)
 WHERE id = :id
```

That's one row-lock per event instead of three, and removes the conditional `WHERE is_enhanced_tracker = 0` second pass.

---

### B-2 — `PlayerPresenceHandler::handleConnect` calls a narrower bot-detection method than the one defined in the same file
**Severity: HIGH (data integrity)**
**File:** `app/Services/Tracker/Handlers/PlayerPresenceHandler.php:67` ↔ `:258` ↔ `app/Services/Tracker/Handlers/AbstractHandler.php:53`

Line 67 calls `$this->nameLooksLikeBot($parsed['name'])` — that resolves to `AbstractHandler::nameLooksLikeBot` (line 53 of AbstractHandler), which only checks `[BOT]` prefix on the name. The same `PlayerPresenceHandler` file defines a *much wider* static `looksLikeBot(?string $realGuid, ?string $name)` at line 258 that also catches:

- `OmniBot…` prefixed names
- GUIDs containing `BOT` (the OmniBot per-slot pattern, e.g. `0000…BOT00018`)

The wider method is unreachable from `handleConnect`. Net effect: any Omnibot whose name doesn't start with `[BOT]` (default `OmniBot07`, `OmniBot08`, etc., plus admin-renamed bots without a `[BOT]` prefix) is treated as a real player and gets a row in `tracker_players`. That contradicts the docblock at lines 70-83 ("Skip bots entirely").

**Suggested fix:** replace line 67 with `self::looksLikeBot($parsed['guid'], $parsed['name'])`. Then either delete `AbstractHandler::nameLooksLikeBot` or have it delegate to the wider version. The static method on `PlayerPresenceHandler` should probably move to `AbstractHandler` so all handlers share the same definition.

---

### B-3 — `pollable()` scope drops freshly-discovered servers that flip to `inactive`
**Severity: HIGH**
**File:** `app/Models/Tracker/TrackerServer.php:82-100` (referenced from `app/Console/Commands/TrackerPollServers.php:25`)

This matches CLAUDE.md known-issue #3 ("scheduler still doesn't poll overdue offline servers"). The scope's `inactive` branch (lines 88-92) requires `first_seen_at <= now()->subDay()`, i.e. only *established* servers (>=24h old). A brand-new server that goes `status='inactive'` within its first day — common for ephemeral/clan servers — falls through the scope forever. `next_poll_at` is irrelevant; the row will never be re-selected even when 12h overdue.

It also affects servers whose `enhanced_last_event_at` is older than 10 minutes (line 97), so an enhanced server that loses connectivity for >10 min and is also `inactive` and <1 day old is doubly stranded.

**Suggested fix:** drop the `subDay()` gate on the inactive branch, OR add a recovery branch like:

```php
->orWhere(function ($q4) {
    $q4->where('is_online', false)
       ->whereNotNull('next_poll_at')
       ->where('next_poll_at', '<=', now()->subMinutes(15)); // recovery cadence
})
```

Either way, drop "established" from the recovery criterion — the whole point of recovery polling is to find out *whether* the server is alive again.

---

### B-4 — Auto-discovery TOCTOU: concurrent first-ever packets create duplicate `tracker_servers` rows
**Severity: HIGH**
**File:** `app/Services/Tracker/Handlers/ServerLifecycleHandler.php:80-171`

`resolveServerId` does three SELECTs (stages 1-3), and only if all three miss does it `insertGetId` at line 145. There's no advisory lock, no `INSERT … ON DUPLICATE KEY UPDATE`, and no unique index on `(ip, port)` (verified: not present in the migration set I checked). With 10 concurrent workers, a brand-new server fleet (multiple packets arriving within milliseconds before the first INSERT commits) produces N duplicate `tracker_servers` rows for the same `(ip, port)`. From that point on, Stage 1's `orderByDesc('is_enhanced_tracker')->orderBy('id')` deterministically picks the lowest id — but the duplicates remain, gradually accumulating `enhanced_event_count` on rows that will never be reconciled.

**Suggested fix:** add a unique index on `(ip, port, game_id)` and use `insertOrIgnore` then re-`SELECT`, or take a Redis lock keyed on the `source_ip` for the duration of resolution+insert. The unique index also prevents accidental admin double-creation.

---

### B-5 — Auto-discovery hardcodes `port = 27960`, breaking Stage 1 lookups for non-default ports
**Severity: MEDIUM**
**File:** `app/Services/Tracker/Handlers/ServerLifecycleHandler.php:148`

Stage 1 (lines 95-106) disambiguates multi-server hosts by matching `(ip, port)` where `port = source_port`. But the auto-create at line 148 unconditionally writes `'port' => 27960`. So:

- A first-packet from `1.2.3.4:27961` creates a row with `port=27960`.
- The next packet from the same `1.2.3.4:27961` misses Stage 1 (port mismatch), then Stage 2 misses (no `enhanced_source_ip` set yet — wait: it *does* set `enhanced_source_ip` at line 156, so Stage 2 may hit), then Stage 3 hits IP-only.

Stage 2 will hit *if* there's only one `enhanced_source_ip = 1.2.3.4` row, but on a multi-port host you get the lowest-id one. So all subsequent ports collapse onto the first auto-discovered server until the Poller runs and corrects `port`. Until that happens, multi-server hosts effectively run with one tracking row.

**Suggested fix:** insert with `'port' => $event->source_port ?? 27960`. Source port is what the gameserver sent the OOB packet from; it's the best guess we have. Add a TODO that `ServerPollerService` will overwrite if wrong.

---

### B-6 — Auto-discovery happens even when the original server has `enhanced_disabled = true`
**Severity: MEDIUM**
**File:** `app/Services/Tracker/Handlers/ServerLifecycleHandler.php:99,115,128,135`

All three resolution stages filter `where('enhanced_disabled', false)`. So an admin who explicitly disabled enhanced tracking on a server (let's say to silence noisy logging) will see that server's packets fall through to auto-discovery, which creates a *second* row for the same IP with `enhanced_disabled = false`. The admin's intent is silently undone.

**Suggested fix:** before falling through to auto-discover, do one more SELECT *without* the `enhanced_disabled = false` filter; if a matching row exists with `enhanced_disabled = true`, return `null` (drop the packet) rather than auto-creating.

---

### B-7 — Concurrent `connect` events on the same `(server_id, slot)` leave duplicate open slot rows
**Severity: MEDIUM**
**File:** `app/Services/Tracker/Handlers/PlayerPresenceHandler.php:138-152`

The "close any stale row" UPDATE (lines 138-142) and the new INSERT (lines 144-152) are not atomic and not in a transaction. The migration `2026_04_18_075300_create_tracker_server_slots_table.php` deliberately omits a UNIQUE on `(server_id, slot)` (line 21-23 of the migration's docblock). If two `connect` packets for the same slot arrive within ms (e.g. packet retransmit, or a fast disconnect+reconnect), both UPDATEs find zero open rows and both INSERTs succeed — leaving two rows with `disconnected_at IS NULL`. The `WeaponStatsHandler` lookup `ORDER BY connected_at DESC LIMIT 1` then non-deterministically picks one.

**Suggested fix:** either wrap the UPDATE+INSERT in a transaction with `SELECT … FOR UPDATE` on the slot, or add a partial unique index on `(server_id, slot) WHERE disconnected_at IS NULL` (MariaDB doesn't support partial unique directly — you'd need a generated column or app-level lock). A simple Redis lock keyed on `slot:{server_id}:{slot}` for ~100ms also works.

---

### B-8 — Stage 2 player linking by `guid_hash` ignores server scope; collides on shared nicknames
**Severity: MEDIUM (data integrity)**
**File:** `app/Services/Tracker/Handlers/PlayerPresenceHandler.php:99-111`

Stage 2 looks up `tracker_players` by `guid_hash = sha256(name_clean)` with `whereNull('real_guid_hash')`. ET nicknames are heavily reused (`Player`, `noob`, `Wolf`, etc.). The first Enhanced connect from anyone using that nickname permanently links the Poller's row for that name to *that one* real GUID, even if the Poller's row was originally created from sightings on totally different servers belonging to different real humans.

The handler logs this as "linked Poller player to Enhanced GUID" (line 106-109) — the log makes the link sound benign, but it's irreversible without a manual DB edit and corrupts ranking/Elo data.

**Suggested fix:** scope Stage 2 to plausible candidates only — e.g., require that the Poller player has been seen on `$serverId` recently (join against `tracker_player_sessions` or whatever tracks last-seen-per-server). Failing that, weaken Stage 2 to only fire if exactly *one* candidate exists across the system, and otherwise create a fresh row.

---

### B-9 — `--server` flag dispatches a job for a non-existent server with no validation
**Severity: LOW**
**File:** `app/Console/Commands/TrackerPollServers.php:19-22`

`PollServerJob::handle` silently returns when `find()` returns null (`PollServerJob.php:27-28`). Operator running `php artisan tracker:poll-servers --server=99999` gets a green `"Dispatched poll job for server #99999"` followed by silent no-op. Not a bug per se; an operator footgun.

**Suggested fix:** `TrackerServer::whereKey($serverId)->exists()` precheck; return non-zero exit code with a useful message.

---

### B-10 — `PollServerJob::__construct` typed as `int`, not `int|TrackerServer`
**Severity: LOW (latent — the file under review doesn't trigger it)**
**File:** `app/Jobs/Tracker/PollServerJob.php:22`

CLAUDE.md flags this; confirmed. Within `TrackerPollServers.php` the call sites (lines 20, 49, 52) all pass an int and are fine. The risk is elsewhere in the codebase calling `PollServerJob::dispatch($server)` with a model. Worth either widening the constructor to `int|TrackerServer` (and resolving inside) or grepping for stray model-passing call sites and asserting they're absent.

---

### B-11 — Race: same server can be dispatched twice if the previous `PollServerJob` is still running when the scheduler ticks
**Severity: MEDIUM**
**File:** `app/Console/Commands/TrackerPollServers.php:25-55` ↔ `app/Jobs/Tracker/PollServerJob.php:36-45`

`PollServerJob` only bumps `next_poll_at` *after* polling completes (`PollServerJob.php:44-45`). With a 10-second job timeout (line 20) and a scheduler that may run every minute, a server stuck for >10s in the queue (or whose worker is busy) will satisfy the `next_poll_at <= now()` filter on the next tick and be re-dispatched. The result is duplicate `PollServerJob` instances for the same server piling up, contributing to queue depth and to ServerPollerService races.

**Suggested fix:** either (a) bump `next_poll_at` immediately on dispatch in the scheduler — pessimistic but correct; (b) wrap `PollServerJob::handle` in a `Cache::lock("poll:{$serverId}", 30)` (Redis is already available); (c) add `withoutOverlapping` middleware to `PollServerJob`.

---

## Performance

### P-1 — `ProcessTrackerEventJob` does at least 4-5 queries per event before the command-specific handler
**Severity: MEDIUM**
**File:** `app/Jobs/ProcessTrackerEventJob.php:58-90`

Per event: `find()` (1) → `ServerLifecycleHandler` (3 UPDATEs + 1 UPDATE for server_id, see B-1) → `refresh()` (1) → command-specific handler (varies) → `update()` for processed flag (1). For keepalives this means ~6 queries to record "yes, server X is still alive" — at 10 workers × dozens of servers the queue is doing tens of thousands of writes/min just on `p` keepalives.

**Suggested fix:** the keepalive `p` command shouldn't go through the full pipeline. Either: (a) bypass the queue entirely for `p` (UDP listener writes a single UPDATE directly, with a per-server in-process throttle); or (b) coalesce keepalives in Redis (e.g. `HSET tracker:keepalive:{server_id} last_at <ts>; HINCRBY ... count 1`) and flush every N seconds via a cron job. Either approach removes the dominant load source.

---

### P-2 — `resolveHandler` instantiates 4 fresh handler objects per event
**Severity: LOW**
**File:** `app/Jobs/ProcessTrackerEventJob.php:111-127`

Trivial allocation, but the array literal at lines 113-118 runs on every single event. Cheap, but unnecessary — these are stateless and could be static/array constants resolved by command name.

**Suggested fix:** keyed lookup by command:

```php
private static array $handlers = [];

private function resolveHandler(string $cmd): ?AbstractHandler {
    if (self::$handlers === []) {
        foreach ([new PlayerPresenceHandler(), new PlayerAliasHandler(),
                  new MatchLifecycleHandler(),  new WeaponStatsHandler()] as $h) {
            foreach ($h->supports() as $c) self::$handlers[$c] = $h;
        }
    }
    return self::$handlers[$cmd] ?? null;
}
```

---

### P-3 — `TrackerPollServers` loads all pollable servers into memory unbounded
**Severity: LOW**
**File:** `app/Console/Commands/TrackerPollServers.php:25-34`

`->get()` materializes the entire result set. At a few thousand servers this is fine; at tens of thousands it'll spike memory on the scheduler tick.

**Suggested fix:** chunk: `TrackerServer::pollable()->where(…)->select(…)->chunkById(500, function ($servers) { … })`.

---

### P-4 — `endStaleSessions()` runs on every scheduler tick regardless of need
**Severity: LOW**
**File:** `app/Console/Commands/TrackerPollServers.php:57`

Every minute (assuming a one-minute scheduler), `PlayerTrackingService::endStaleSessions` runs. If it's a heavy table scan, that compounds with poll dispatch load.

**Suggested fix:** either move to its own scheduled command at 5-min cadence, or guard with `Cache::lock` so it runs at most once per N minutes regardless of caller.

---

## Refactor

### R-1 — `guessGameIdForIp` is a constant masquerading as logic
**Severity: LOW**
**File:** `app/Services/Tracker/Handlers/ServerLifecycleHandler.php:177-182`

The method always returns `1`. The name promises dynamism. Either inline the literal at the call site (line 146) with a comment "Enhanced Tracker is ET-only (game_id=1)", or replace the method with a class constant `const ENHANCED_TRACKER_GAME_ID = 1`.

---

### R-2 — `PlayerPresenceHandler::resolveServerId` is weaker than `ServerLifecycleHandler::resolveServerId` and silently drifts
**Severity: LOW**
**File:** `app/Services/Tracker/Handlers/PlayerPresenceHandler.php:235-246`

Two different `resolveServerId` implementations exist with different multi-server-host handling. In practice `PlayerPresenceHandler::resolveServerId` is unreachable in normal flow because `ServerLifecycleHandler` runs first and sets `event->server_id` (the `$event->server_id ??` short-circuit at line 43 wins). But the dead path is a bug magnet — if pipeline order ever changes, presence events will misroute on multi-server hosts.

**Suggested fix:** either delete `resolveServerId` from `PlayerPresenceHandler` (asserting `server_id` is always pre-resolved upstream), or move `ServerLifecycleHandler::resolveServerId` to a shared service so both handlers use the strict version.

---

### R-3 — Static `looksLikeBot` on `PlayerPresenceHandler` duplicates and contradicts `AbstractHandler::nameLooksLikeBot`
**Severity: LOW (related to B-2)**
**File:** `app/Services/Tracker/Handlers/PlayerPresenceHandler.php:258-276` ↔ `app/Services/Tracker/Handlers/AbstractHandler.php:53-57`

Two implementations with different semantics, neither calls the other, and the wider one isn't used by `handleConnect` (B-2). Pick one, kill the other. Recommended: keep the wider three-pattern version on `AbstractHandler` so all handlers benefit.

---

### R-4 — `ProcessTrackerEventJob` updates `processing_error` outside any transaction; partial state can persist
**Severity: LOW**
**File:** `app/Jobs/ProcessTrackerEventJob.php:91-104`

If `ServerLifecycleHandler` partially writes (e.g. its first UPDATE succeeds, second deadlocks and rolls back), the catch block writes `processing_error` on `tracker_raw_events` and re-throws. The handler's own `DB::transaction` correctly rolls back its writes — but the lifecycle "update event with server_id" is also inside that transaction (line 39) so on rollback the event keeps `server_id = null`. The retry then re-runs the whole lifecycle handler, which re-resolves the server. Fine in steady state, but means deadlock retries multiply DB load 3× (3 retries) before failing.

No specific fix beyond B-1 (which removes the deadlock surface). Worth noting in case retries ever get tuned.

---

## Style / Maintainability

### S-1 — Comment block in `ServerLifecycleHandler::resolveServerId` is excellent; mirror the style elsewhere
**Severity: LOW (positive)**
**File:** `app/Services/Tracker/Handlers/ServerLifecycleHandler.php:87-130`

The multi-stage matching comments are precise and explain *why* each stage exists, in the order they exist. This is the kind of comment you want when the next person debugs a misrouted server. `PlayerPresenceHandler::resolveServerId` (line 235) has none — it should at least cross-reference the canonical version with "see ServerLifecycleHandler::resolveServerId for the full multi-stage logic; this is a fallback".

---

### S-2 — `ProcessTrackerEventJob::handle` truncates trace to 1500 chars and error to 255
**Severity: LOW**
**File:** `app/Jobs/ProcessTrackerEventJob.php:96, 100`

Both are fine — `processing_error` column is presumably VARCHAR(255). Worth a one-liner comment so the next dev doesn't widen the column thinking they'll get longer messages.

---

## Out of scope but noticed

- The `tracker_player_sessions` deadlock surface mentioned in CLAUDE.md issue #1 is *not* in the four files I reviewed. To confirm the architectural fix (Redis buffer + batch flush), read `app/Services/Tracker/PlayerTrackingService.php` next.
- `WeaponStatsHandler` was not in scope; it relies on the slot-occupancy table touched by B-7 — verify slot-resolution is robust there before signing off on B-7.
- The `enhanced_event_count` increment (B-1, line 53) doesn't get a `last_seen_at` companion update. If `tracker_servers.last_seen_at` is supposed to advance with every Enhanced event, that's a separate bug; if it's Poller-owned (Decision A analogue for servers) it's intentional. Worth confirming with the dev.

---

## Recommended priorities

1. **B-1** (deadlock root cause) — single biggest lever for the 102k failed jobs.
2. **B-2** (bot detection regression) — silently corrupts the players table.
3. **B-3** (scheduler skips offline-young servers) — already on the priority list in CLAUDE.md.
4. **B-4 / B-5 / B-6** (auto-discovery races and bugs) — fix together; they share the same code path.
5. **B-11** (duplicate poll dispatch) — likely contributes to queue depth and is easy to fix with a Redis lock.
6. The rest can be scheduled as a cleanup PR.

No patches were applied to `main`, per request.
