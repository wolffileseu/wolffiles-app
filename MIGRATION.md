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
