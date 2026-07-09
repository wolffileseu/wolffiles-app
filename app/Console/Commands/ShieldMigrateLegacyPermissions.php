<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Migrates the legacy custom permission naming scheme ({action}_{snake_resource},
 * e.g. `view_files`, `create_tracker_servers`, `view_fastdl_monitor`) onto the
 * permission names that Filament Shield generates ({action}_{model words joined
 * by `::`}, e.g. `view_any_file` + `view_file`, `create_tracker::server`,
 * `page_FastDlMonitor`).
 *
 * The command is idempotent and purely ADDITIVE:
 *   - it re-assigns the mapped Shield permissions to every existing role,
 *   - it grants the `super_admin` role to every user that currently holds `admin`,
 *   - it NEVER deletes the old permissions (cleanup is a later manual step),
 *   - unmapped legacy permissions are logged, never fatal.
 *
 * Run `--dry-run` to preview the full mapping without touching the database.
 */
class ShieldMigrateLegacyPermissions extends Command
{
    protected $signature = 'shield:migrate-legacy-permissions
                            {--dry-run : Print the computed mapping without writing anything}';

    protected $description = 'Map legacy custom permission names onto Filament Shield permissions and re-assign them to roles.';

    /**
     * Legacy resource-subject fragments that do not snake-case cleanly onto the
     * Shield model slug. Applied to the subject before singularise + `::` slugify.
     * e.g. legacy `fastdl_clans` -> `fast_dl_clans` -> `fast::dl::clan`.
     */
    private array $subjectRewrites = [
        'fastdl' => 'fast_dl',
    ];

    /**
     * Legacy page/widget permission names that cannot be derived generically
     * (StudlyCase of the subject does not match the Filament class name).
     */
    private array $explicitAliases = [
        'view_fastdl_monitor'      => 'page_FastDlMonitor',
        'view_translation_manager' => 'page_TranslationManager',
    ];

    /**
     * Legacy action prefix => list of Shield action prefixes it expands to.
     * Order matters: longest prefixes are matched first.
     */
    private array $actionExpansion = [
        'force_delete_any' => ['force_delete_any'],
        'force_delete'     => ['force_delete'],
        'restore_any'      => ['restore_any'],
        'restore'          => ['restore'],
        'delete_any'       => ['delete_any'],
        'delete'           => ['delete'],
        'view_any'         => ['view_any'],
        'view'             => ['view', 'view_any'],
        'create'           => ['create'],
        'update'           => ['update'],
        'replicate'        => ['replicate'],
        'reorder'          => ['reorder'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Make sure Spatie's in-memory cache reflects the DB before we begin.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $shieldPerms = Permission::pluck('name')->all();
        $shieldSet = array_flip($shieldPerms);

        if (empty($shieldPerms)) {
            $this->error('No permissions found. Run `php artisan shield:generate --all` first.');

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Migrating legacy permissions to Filament Shield naming');
        $this->newLine();

        $unmapped = [];

        // ---- 1. Re-assign mapped permissions to every role --------------------
        foreach (Role::with('permissions')->get() as $role) {
            $current = $role->permissions->pluck('name')->all();
            $grants = [];
            $rows = [];

            foreach ($current as $legacy) {
                $targets = $this->mapLegacy($legacy, $shieldSet);

                if (empty($targets)) {
                    // A name that is already a Shield permission is not "unmapped";
                    // mapLegacy() returns it verbatim in that case, so reaching here
                    // means we genuinely could not resolve it.
                    $unmapped[$legacy] = ($unmapped[$legacy] ?? 0) + 1;
                    $rows[] = [$legacy, '<fg=yellow>UNMAPPED</>'];

                    continue;
                }

                foreach ($targets as $t) {
                    $grants[$t] = true;
                }
                $rows[] = [$legacy, implode(', ', $targets)];
            }

            $grantNames = array_keys($grants);

            $this->line("<options=bold>Role: {$role->name}</> ({$role->permissions->count()} legacy perms -> ".count($grantNames).' shield perms)');
            if (! empty($rows) && $this->output->isVerbose()) {
                $this->table(['Legacy', 'Shield target(s)'], $rows);
            }

            if (! $dryRun && ! empty($grantNames)) {
                // Additive: keep existing assignments, add the mapped Shield ones.
                $role->givePermissionTo($grantNames);
            }
        }

        $this->newLine();

        // ---- 2. Promote legacy `admin` users to `super_admin` -----------------
        $superAdminName = config('filament-shield.super_admin.name', 'super_admin');
        $superAdmin = Role::where('name', $superAdminName)->first();

        if (! $superAdmin) {
            $this->warn("Role `{$superAdminName}` does not exist yet.");
            if (! $dryRun) {
                $superAdmin = Role::create(['name' => $superAdminName, 'guard_name' => 'web']);
                $superAdmin->syncPermissions(Permission::all());
                $this->info("Created `{$superAdminName}` and granted all permissions.");
            }
        }

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminUsers = $adminRole->users()->get();
            $this->line("<options=bold>Users with legacy `admin` role:</> {$adminUsers->count()} -> assign `{$superAdminName}`");

            if (! $dryRun && $superAdmin) {
                foreach ($adminUsers as $user) {
                    if (! $user->hasRole($superAdminName)) {
                        $user->assignRole($superAdminName);
                    }
                }
            }
        } else {
            $this->line('No legacy `admin` role found — nothing to promote.');
        }

        $this->newLine();

        // ---- 3. Report unmapped legacy permissions ----------------------------
        if (! empty($unmapped)) {
            $this->warn('Unmapped legacy permissions (left untouched, no Shield equivalent):');
            foreach ($unmapped as $name => $count) {
                $this->line("  - {$name} (on {$count} role(s))");
                Log::warning('shield:migrate-legacy-permissions unmapped permission', [
                    'permission' => $name,
                    'roles'      => $count,
                ]);
            }
        } else {
            $this->info('All legacy permissions mapped successfully.');
        }

        if (! $dryRun) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry run complete — no changes written.'
            : 'Migration complete.');

        return self::SUCCESS;
    }

    /**
     * Resolve a legacy permission name to the list of Shield permission names it
     * maps to (only those that actually exist). Returns [] when nothing matches.
     */
    private function mapLegacy(string $legacy, array $shieldSet): array
    {
        // Structurally-canonical Shield names — needed for idempotent re-runs.
        // NOTE: we cannot use "exists in the permissions table" as the test here,
        // because after shield:generate the legacy permissions ALSO still live in
        // that table (they are never deleted), so a legacy name would match
        // itself. Instead we detect Shield's own naming shape:
        //   - page_* / widget_*        (page & widget permissions)
        //   - anything containing `::` (multi-word resource permissions)
        // Legacy names are plain snake_case and never take these shapes.
        if (str_starts_with($legacy, 'page_') || str_starts_with($legacy, 'widget_')) {
            return isset($shieldSet[$legacy]) ? [$legacy] : [];
        }
        if (str_contains($legacy, '::')) {
            return isset($shieldSet[$legacy]) ? [$legacy] : [];
        }

        // Explicit page/widget aliases.
        if (isset($this->explicitAliases[$legacy])) {
            $alias = $this->explicitAliases[$legacy];

            return isset($shieldSet[$alias]) ? [$alias] : [];
        }

        // Split off the longest matching legacy action prefix.
        [$prefix, $subject] = $this->splitAction($legacy);
        if ($prefix === null) {
            return [];
        }

        // Generic page / widget detection: StudlyCase(subject) === class name.
        $studly = Str::studly($subject);
        foreach (['page', 'widget'] as $kind) {
            $candidate = "{$kind}_{$studly}";
            if (isset($shieldSet[$candidate])) {
                return [$candidate];
            }
        }

        // Resource permission: normalise subject -> singular -> `::` slug.
        $normalised = strtr($subject, $this->subjectRewrites);
        $slug = str_replace('_', '::', Str::singular($normalised));

        $targets = [];
        foreach ($this->actionExpansion[$prefix] as $shieldAction) {
            $candidate = "{$shieldAction}_{$slug}";
            if (isset($shieldSet[$candidate])) {
                $targets[$candidate] = true;
            }
        }

        return array_keys($targets);
    }

    /**
     * Return [actionPrefix, subject] for a legacy name, matching the longest
     * known action prefix first. Returns [null, name] when no prefix matches.
     */
    private function splitAction(string $legacy): array
    {
        // actionExpansion is already ordered longest-first.
        foreach (array_keys($this->actionExpansion) as $prefix) {
            if (str_starts_with($legacy, $prefix.'_')) {
                return [$prefix, substr($legacy, strlen($prefix) + 1)];
            }
        }

        return [null, $legacy];
    }
}
