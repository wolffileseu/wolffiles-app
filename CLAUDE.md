# Wolffiles.eu — Claude Code Context

## Stack
- **Laravel 12** + **Filament 3** (admin) + Blade + Alpine.js + Livewire
- **PHP 8.3**, **MariaDB**, **Redis** (queues, cache, locking)
- **Tailwind CSS** dark-only, hardcoded `gray-900` palette
- **Vite** bundling, **6 Sprachen** (DE master, EN/FR/NL/PL/TR)
- **MeiliSearch + Qdrant** (semantic search via Mistral embeddings)
- **Hetzner Object Storage** (S3-compatible) via Flysystem

## Production Infrastructure
- Hetzner dedicated, AlmaLinux 9, Plesk, Apache, PHP-FPM
- App path on prod: `/var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app/`
- App user: `wolffiles` | Plesk user: `wolffiles.eu_lkiogmaiktl`
- 36 systemd queue workers on Redis:
  - `wolffiles-worker-tracker@1..10` → queue `tracker` (ProcessTrackerEventJob)
  - `wolffiles-worker-tracker-high@1..10` → queue `tracker-high` (PollServerJob)
  - `wolffiles-worker-tracker-low@1..16` → queue `tracker-low`
- PM2: `tracker-listener` (UDP port 4444 for Enhanced ETLegacy servers)

## Known Issues to Investigate (priority order)

### 🔴 High priority
1. **failed_jobs table has 102k+ entries** — almost all from `tracker` queue, MySQL deadlocks
   on `ProcessTrackerEventJob` updating `tracker_player_sessions` + `tracker_servers`.
   Architectural fix: Redis buffer + batch flush per server in a single transaction.

2. **`PollServerJob::__construct` accepts only `int $serverId`, not TrackerServer model.**
   Causes TypeError when called with `dispatch($server)`. Should be `int|TrackerServer`.

3. **`tracker:poll-servers` skips overdue offline servers.** When `is_online=false` and
   `next_poll_at` is hours overdue, scheduler still doesn't poll. Recovery polling broken
   — servers stay marked offline indefinitely until manual `PollServerJob::dispatchSync()`.
   See `app/Console/Commands/TrackerPollServers.php` and `app/Services/Tracker/ServerPollerService.php`.

### 🟡 Medium priority
4. **`scripts/start-tracker-listener.sh` hardcodes `-v` flag** — produces 5+ log lines/sec
   in PM2 logs. Should be configurable via `LISTENER_VERBOSE=1` env var.

5. **No auto-prune for failed_jobs.** Table just grows. Need `tracker:prune-failed-jobs`
   command + daily cron at 04:00.

### 🟢 Low priority / nice to have
6. Wiki Phases 6–12 incomplete (we shipped 1–5):
   - File embeds with thumbnails
   - Template namespace + transclusion
   - Talk page UI (DB exists, no frontend yet)
   - Edit history diff viewer
   - User contributions special page
   - Search integration (MeiliSearch)
   - WYSIWYG editor option in Filament

## Architecture Conventions

### Tracker
- `tracker_servers.os` should be VARCHAR(255) minimum (ETLegacy version strings are long)
- `ServerLifecycleHandler::resolveServerId()` stage order: IP+source_port → enhanced_source_ip → IP-only
- ETDS dual-port: `TB_Send()` only sends from main socket by design
- `last_poll_at` is correctly set in BOTH `handleOffline()` branches (no bug here)
- ET game server cvar values are Latin-1 encoded — must `mb_convert_encoding()` before
  writing to MySQL `utf8mb4` columns

### Wiki (just shipped — Phases 1-5)
- Wikitext parser uses `\x02` control char tokens to survive `htmlspecialchars`
- Special page routes MUST be defined BEFORE catch-all `/wiki/{slug}`
- Master+Translations model: `wiki_articles` (master) + `wiki_article_translations` per locale
- Admin-only editing via Filament (no public user editing yet)

### General
- All Filament resource URLs need slug passed explicitly:
  `route('filament.admin.resources.X.edit', ['record' => $model->slug])`
- Tailwind JIT purges dynamically-constructed class names — use safelists or inline styles
- Translations are additive only — never overwrite existing keys
- 6-language support: DE (master), EN/FR/NL/PL/TR
- All tracker queues are Redis since 2026-05-02 (MySQL caused 540k deadlocks/month)

## What I Want From You

When analyzing this codebase, **first read** rather than patch. Produce findings as:

1. **`FINDINGS.md`** with categorized issues (Bug / Performance / Security / Refactor / Style)
2. Each finding: file path, line numbers, severity, description, suggested fix
3. **No automatic patches to `main`** — propose, don't push. I'll review and prioritize.
4. Focus areas (in priority order):
   - `app/Jobs/Tracker/*` and `app/Services/Tracker/*` (deadlock investigation)
   - `app/Console/Commands/Tracker*.php` (scheduler issues)
   - `app/Services/Wiki/WikitextParser.php` (security: XSS surface area)
   - `app/Http/Controllers/Frontend/*` (rate limiting, auth checks)
   - Database migrations: any missing indexes on hot query columns

## What to IGNORE
- Style nitpicks (we use Pint, run separately)
- Comments in German — that's intentional, the dev is German
- `httpdocs_new` references in older commits — migration was completed 2026-05-04
- "Hardcoded" gray-900 colors — that's the design choice, not a bug

## CRITICAL: Active Bug Findings (2026-05-14 from initial analysis)

See FINDINGS.md for full details. Highest-priority items:

- **B-1**: ServerLifecycleHandler does 3 sequential UPDATEs per event → deadlock root cause. Fix by collapsing into single UPDATE.
- **B-2**: Most Omnibots silently treated as real players (dead-code looksLikeBot()). Stats currently corrupted.
- **B-3**: Confirmed pollable() scope drops fresh-then-offline servers forever.
- **B-7**: Missing unique index on (server_id, slot) WHERE open=true causes duplicate slot rows.
- **B-8**: Stage-2 player linking by guid_hash unscoped — cross-links humans with same nickname.

Path corrections from initial CLAUDE.md:
- `app/Jobs/ProcessTrackerEventJob.php` (NOT `app/Jobs/Tracker/`)
- `app/Services/Tracker/Handlers/*Handler.php` (NOT `app/Services/Tracker/`)
