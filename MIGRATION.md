# Filament Shield Migration — Production Deploy Guide

This branch (`feature/filament-shield`) migrates the admin panel from the custom
role/permission system to **Filament Shield** (`bezhansalleh/filament-shield`).

- Permissions are now **policy-driven**. Every Filament Resource, Page and Widget
  has a generated Policy under `app/Policies/` (committed to git).
- Access is no longer gated by hardcoded `canAccess()` methods — those were removed.
- The custom `RoleResource` was replaced by Shield's own role manager at
  **`/admin/shield/roles`**.
- `super_admin` (role name `super_admin`) bypasses all checks; `panel_user` and
  "any permission" also grant panel access (see `User::canAccessPanel()`).

Shield's **permissions live in the database** and are NOT in git. They must be
(re)generated on production, and the legacy role assignments must be mapped onto
the new Shield permission names. That is what the steps below do.

---

## Production environment reference (Plesk / AlmaLinux / MariaDB)

| Item          | Value                                                          |
|---------------|----------------------------------------------------------------|
| App path      | `/var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app/`         |
| PHP binary    | `/opt/plesk/php/8.3/bin/php`                                    |
| Composer      | `/usr/local/psa/var/modules/composer/composer.phar`            |
| Run as user   | `wolffiles.eu_lkiogmaiktl`                                      |

Run **every** command below as the Plesk user, from the app path. Prefix each
`php`/`composer` invocation with the correct binary as shown.

```bash
# become the site user & cd into the app
sudo -u wolffiles.eu_lkiogmaiktl -s
cd /var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app
```

> **Back up the database first.** The migration writes to `roles`,
> `permissions`, `role_has_permissions`, `model_has_permissions` and
> `model_has_roles`. Take a `mysqldump` of at least those tables.

---

## Deploy steps

### 1. Pull the branch & install the dependency

```bash
git fetch origin
git checkout feature/filament-shield
git pull --ff-only

/opt/plesk/php/8.3/bin/php /usr/local/psa/var/modules/composer/composer.phar \
    install --no-dev --optimize-autoloader --no-interaction
```

Nothing else needs publishing: `config/filament-shield.php` is committed, the
plugin is registered in `AdminPanelProvider`, and all Policies are in git.

### 2. Generate the Shield permission rows in the DB

Policies are already deployed via git, so generate **permissions only** (this
avoids overwriting the committed policy files and any interactive prompts):

```bash
/opt/plesk/php/8.3/bin/php artisan shield:generate --all \
    --option=permissions --panel=admin --no-interaction
```

This creates ~722 Shield permissions (e.g. `view_any_file`, `create_tracker::server`,
`page_FastDlMonitor`, `widget_StatsOverview`) and ensures the `super_admin` role
exists with all permissions granted.

> Multi-word models use `::` as the word separator, e.g. `TrackerServer`
> → `tracker::server`, `WikiArticle` → `wiki::article`, `FastDlFile`
> → `fast::dl::file`. This is expected Shield behaviour.

### 3. Preview the legacy → Shield mapping (dry run)

```bash
/opt/plesk/php/8.3/bin/php artisan shield:migrate-legacy-permissions --dry-run
# add -v to see the full per-permission mapping table
```

Review the summary. In particular check the **"Unmapped legacy permissions"**
list — those have no Shield equivalent (e.g. `view_tracker_maps`, because there
is no `TrackerMap` resource) and are left untouched. Confirm nothing important
is unexpectedly unmapped.

### 4. Apply the migration

```bash
/opt/plesk/php/8.3/bin/php artisan shield:migrate-legacy-permissions
```

The command is **idempotent** and **purely additive**:

- Re-assigns the mapped Shield permissions to every existing role, mapping:
  - `view_X`  → `view_any_x` **and** `view_x`
  - `create_X` → `create_x`
  - `update_X` → `update_x`
  - `delete_X` → `delete_x`
  - plural/singular is normalised (`view_files` → `view_file`)
  - the `fastdl_*` legacy names map onto Shield's `fast::dl::*`
  - page permissions (`view_fastdl_monitor` → `page_FastDlMonitor`, etc.)
- Grants the `super_admin` role to every user that currently has the `admin` role.
- **Never deletes** the old permissions (cleanup is a later manual step).
- Logs any unmapped permissions to `laravel.log` (`Log::warning`).

Safe to run more than once.

### 5. Clear caches

```bash
/opt/plesk/php/8.3/bin/php artisan permission:cache-reset
/opt/plesk/php/8.3/bin/php artisan optimize:clear
/opt/plesk/php/8.3/bin/php artisan filament:optimize
```

### 6. Smoke test

- Log in as an `admin`/`super_admin` user → full panel access, and
  **Roles** manager visible at `/admin/shield/roles`.
- Log in as a `moderator` → only the resources/pages their permissions allow.
- Confirm the custom pages (Translations, FastDL Monitor, Player Merge, …) are
  reachable only by users holding the matching `page_*` permission.

---

## Post-migration cleanup (do later, manually — NOT part of this deploy)

Once you have verified everything works and no code references the old names:

1. Remove the legacy permissions from the DB (the ones without `::` /
   `page_` / `widget_` shape that Shield did not generate), e.g. via a small
   one-off tinker script or a follow-up command.
2. Remove the now-redundant `admin`/`moderator` roles once all users have been
   moved onto `super_admin`/`panel_user` + granular permissions.
3. In `app/Providers/AppServiceProvider.php` the legacy super-admin safety net
   `Gate::before(fn ($user) => $user->hasRole('admin') ? true : null)` can be
   dropped once no user relies on the bare `admin` role — Shield already
   registers its own `super_admin` gate.

---

## Rollback

Because the migration is additive and does not delete anything:

1. `git checkout main` and `composer install` to restore the previous code.
2. The old permissions and role assignments are still intact in the DB, so the
   pre-Shield custom `RoleResource`/`canAccess()` logic works again immediately.
3. Optionally remove the Shield-generated permissions and the `super_admin`
   role if you want a clean revert (not required for functionality).

---

# Laravel 12.x Dependency Refresh + CMS 1.0.0 — Production Deploy Guide

This branch (`chore/laravel-12-patch-cms-1.0`) does **not** upgrade the major
framework version. A Laravel 13 upgrade was investigated and **deferred**: the
whole Filament 3 stack (`filament/filament` and friends) caps at
`illuminate/* ^12.0`, and `bezhansalleh/filament-shield 3.9` is pinned to
`filament/filament ^3.2`. Reaching Laravel 13 therefore requires a **Filament
3 → 4/5 + Shield 3 → 4 major migration** (breaking changes across every
Resource/Page/Widget), which is out of scope for a dependency refresh. See the
"Laravel 13 status" note at the bottom.

What this branch actually ships:

- `composer update` **within the existing `^12` constraints** — no
  `composer.json` version constraints were changed. Notable bumps:
  - `laravel/framework` `v12.51.0 → v12.63.0`
  - `filament/*` `v3.3.48 → v3.3.54` (patch)
  - `livewire/livewire` `v3.7.10 → v3.8.2`
  - `spatie/laravel-permission` `6.24.1 → 6.25.0`, `laravel/sanctum` `4.3.1 → 4.3.2`
  - plus Symfony/AWS/Guzzle/Carbon patch bumps. `composer audit` reports **no
    known security advisories**.
- Republished Filament front-end assets under `public/js/filament/` and
  `public/css/filament/` (regenerated by `filament:upgrade`, matching 3.3.54).
- **CMS version bumped `0.1.0 → 1.0.0`** in `config/app.php` (`app.version`,
  rendered in the footer via `config('app.version')`).

No database migrations were added or changed — nothing to migrate on deploy.

## Production environment reference (Plesk / AlmaLinux / MariaDB)

Same as the Shield section above:

| Item          | Value                                                          |
|---------------|----------------------------------------------------------------|
| App path      | `/var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app/`         |
| PHP binary    | `/opt/plesk/php/8.3/bin/php`                                    |
| Composer      | `/usr/local/psa/var/modules/composer/composer.phar`            |
| Run as user   | `wolffiles.eu_lkiogmaiktl`                                      |

## Deploy steps

Run every command as the Plesk user (`wolffiles.eu_lkiogmaiktl`), from the app path.

```bash
cd /var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app/

# 1. Pull the branch (or merge to main first, then pull main)
git pull

# 2. Install the locked dependencies (production, no dev, optimized autoloader).
#    post-autoload-dump runs `filament:upgrade`, which republishes the Filament
#    assets — matching the versions committed in this branch.
/opt/plesk/php/8.3/bin/php /usr/local/psa/var/modules/composer/composer.phar \
    install --no-dev --optimize-autoloader --no-interaction

# 3. Rebuild caches for the new framework/config version
/opt/plesk/php/8.3/bin/php artisan config:clear
/opt/plesk/php/8.3/bin/php artisan config:cache
/opt/plesk/php/8.3/bin/php artisan route:cache
/opt/plesk/php/8.3/bin/php artisan view:cache
/opt/plesk/php/8.3/bin/php artisan filament:cache-components

# 4. No migrations in this release, but run --force for safety (no-op expected)
/opt/plesk/php/8.3/bin/php artisan migrate --force

# 5. Restart the 36 queue workers + PM2 listener so they pick up the new code
sudo systemctl restart 'wolffiles-worker-tracker@*' \
    'wolffiles-worker-tracker-high@*' 'wolffiles-worker-tracker-low@*'
pm2 restart tracker-listener

# 6. Sanity check
/opt/plesk/php/8.3/bin/php artisan about | grep -i version
```

Verify the footer reads **`Wolffiles CMS — v.1.0.0`** after deploy.

## Rollback

`composer.json` constraints are unchanged, so rollback is trivial:

1. `git checkout main` (or the previous commit) and re-run the `composer install`
   + cache-rebuild steps above with the restored `composer.lock`.
2. No schema changes were made, so the database is untouched — no DB rollback needed.

## Laravel 13 status (deferred)

Laravel 13 remains blocked until Filament is upgraded. When ready, the path is:

- `filament/filament` `^3.2 → ^4.0` (or `^5.0`) and follow Filament's own upgrade
  guide — expect breaking changes across every `app/Filament/**` Resource, Page
  and Widget.
- `bezhansalleh/filament-shield` `^3.9 → ^4.0` (requires `filament ^4.0|^5.0`),
  re-verifying the policy-driven permission setup from the Shield migration.
- Only then bump `laravel/framework` to `^13.0` and run the official
  <https://laravel.com/docs/13.x/upgrade> guide.

Treat that as a separate, planned Filament-major project — not a drop-in bump.

---

# Filament 3 → 4 → 5 + Shield 3 → 4 Migration

Branch: `feature/filament-5`. Filament was upgraded 3 → 4 → 5 and
`bezhansalleh/filament-shield` 3.9 → 4.x. Livewire moves to 4 as part of
Filament 5. This is a behavioural migration — read the notes below before deploy.

## Shield 4: legacy permission-key compatibility (CRITICAL)

Shield 4 changed its default permission-key format (pascal case + `:` separator,
e.g. `ViewAny:Post`). Production stores permissions in the **legacy Shield 3.x
format** (`view_any_tracker::server`, `page_FastDlMonitor`, `widget_StatsOverview`)
and those names **must not change** or every existing role→permission assignment
breaks.

To preserve them, `AppServiceProvider::boot()` registers
`FilamentShield::buildPermissionKeyUsing()` reproducing the exact 3.x naming:

- **Resources**: `{snake_affix}_{snake('::')_subject}`, where the *subject* is the
  resource's path relative to `Resources\` with backslashes stripped and the
  `Resource` suffix removed — **not** the model basename. This matters where they
  diverge:
  - `BugTracker\TaskResource` → `bug::tracker::task` (not `task`)
  - `PlayerReportResource` (model `TrackerPlayerReport`) → `player::report`
    (not `tracker::player::report`)
- **Pages**: `page_{ClassBasename}` (e.g. `page_FastDlMonitor`)
- **Widgets**: `widget_{ClassBasename}` (e.g. `widget_StatsOverview`)
- **Special case**: Shield's own `RoleResource` moved to `Resources\Roles\` in v4;
  it is pinned back to the `role` subject so `view_any_role` etc. stay valid.

This closure governs both permission generation *and* the runtime
`HasPageShield`/`HasWidgetShield` access checks. Verified: all 702 existing
production permission keys are reproduced unchanged (the only additions are 6
additive `*_role` methods Shield 4 generates for the role resource).

`super_admin` keeps `define_via_gate => false` (matching the old config): the role
works by **holding all permissions** in the DB, so `shield:generate` must be run
on deploy to grant any newly added permissions. `User::canAccessPanel()` is
unchanged (super_admin OR panel_user OR any permission).

The old `config/filament-shield.php` was deleted and the Shield 4 config published
fresh (`vendor:publish --tag=filament-shield-config`).

## Filament 4 behaviour changes to be aware of

- **File visibility defaults to `private` on non-local disks.** In Filament 3,
  S3 uploads were effectively public. Every public-facing `FileUpload` on the
  `s3` disk now carries an explicit `->visibility('public')` (avatars,
  screenshots, wallpapers, fastdl, posts, categories, partners, lua scripts,
  tutorials, wiki attachments, page PDFs…). Genuinely private uploads
  (`demos`, `ban-evidence`) keep `->visibility('private')`. If you add a new S3
  `FileUpload` that must be reachable via cdn.wolffiles.eu, set
  `->visibility('public')` explicitly.
- **Table filters are deferred by default.** Filters no longer apply live as you
  change them — the user must click **Apply**. If you want the old live
  behaviour on a specific table, add `->deferFilters(false)`.
- **`unique()` validation defaults to `ignoreRecord: true`** on edit forms.
  Existing explicit `unique(ignoreRecord: true)` calls are unaffected; just be
  aware the default flipped.
- **Grid/column span defaults changed.** Components now span a single column by
  default in more contexts; if a field that used to stretch full-width no longer
  does, add `->columnSpanFull()` (or an explicit `->columnSpan(...)`).

## Code migration notes

- Ran `vendor/bin/filament-v4` then `filament-v5` (Rector). These also enabled
  `importNames()`/`importShortClasses()`, which normalised fully-qualified class
  references into `use` imports across `app/` — a large but purely cosmetic diff.
- Manual fixes Rector missed: `->reactive()` → `->live()`;
  `callable $get`/`callable $set` → `Filament\Schemas\Components\Utilities\Get`/`Set`.
- `form()`/`infolist()` signatures are now `Schema $schema): Schema` with
  `->components([...])`. Infolist entry classes
  (`Filament\Infolists\Components\TextEntry` etc.) still resolve in Filament 4/5,
  so their imports were left as-is.
