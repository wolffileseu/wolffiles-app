<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * PM (Private Messages) Permission Seeder.
 *
 * Idempotent: safe to re-run.
 *
 * Permission strategy:
 * - User-level checks (send PM, view own inbox) are NOT spatie permissions.
 *   They are enforced in PmService via privacy settings, block lists, rate limits.
 * - Admin/Mod-level checks ARE spatie permissions, granted to roles.
 *
 * Permissions (matches existing naming convention verb_resource):
 *   - view_any_pm_message_report     (admin, moderator)
 *   - update_pm_message_report       (admin, moderator)
 *   - view_any_pm_conversation       (admin only)
 *   - create_pm_evidence_snapshot    (admin, moderator)
 *   - lock_pm_conversation           (admin, moderator)
 *   - view_any_pm_admin_access_log   (admin only)
 */
class PmPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // -----------------------------------------------------------------
        // 1. Define permissions and their target roles
        // -----------------------------------------------------------------
        $permissionRoleMap = [
            'view_any_pm_message_report'   => ['admin', 'moderator'],
            'update_pm_message_report'     => ['admin', 'moderator'],
            'create_pm_evidence_snapshot'  => ['admin', 'moderator'],
            'lock_pm_conversation'         => ['admin', 'moderator'],
            'view_any_pm_conversation'     => ['admin'],
            'view_any_pm_admin_access_log' => ['admin'],
        ];

        // -----------------------------------------------------------------
        // 2. Create permissions (idempotent)
        // -----------------------------------------------------------------
        $created = 0;
        $existed = 0;
        foreach (array_keys($permissionRoleMap) as $name) {
            $permission = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => $guard,
            ]);
            if ($permission->wasRecentlyCreated) {
                $created++;
                $this->command->info("  Created permission: {$name}");
            } else {
                $existed++;
                $this->command->line("  Already exists: {$name}");
            }
        }

        $this->command->info("Permissions: {$created} created, {$existed} already existed.");

        // -----------------------------------------------------------------
        // 3. Assign permissions to roles (idempotent via givePermissionTo)
        // -----------------------------------------------------------------
        $assigned = 0;
        foreach ($permissionRoleMap as $permissionName => $roleNames) {
            foreach ($roleNames as $roleName) {
                $role = Role::where('name', $roleName)
                    ->where('guard_name', $guard)
                    ->first();

                if (! $role) {
                    $this->command->warn("  Role not found: {$roleName} — skipping");
                    continue;
                }

                if (! $role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permissionName);
                    $assigned++;
                    $this->command->info("  Assigned {$permissionName} -> {$roleName}");
                }
            }
        }

        $this->command->info("Role-permission assignments: {$assigned} new.");

        // -----------------------------------------------------------------
        // 4. Reset spatie permission cache so changes take effect immediately
        // -----------------------------------------------------------------
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info("Permission cache cleared.");
    }
}
