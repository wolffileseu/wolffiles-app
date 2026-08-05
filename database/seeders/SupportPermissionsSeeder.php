<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SupportPermissionsSeeder extends Seeder
{
    /**
     * Custom-Permissions fuer das Support-Modul.
     * Die CRUD-Permissions (view_any_support::ticket usw.) erzeugt Shield selbst.
     */
    public const PERMISSIONS = [
        'support_view_all'          => 'Alle Tickets sehen, nicht nur eigene/abonnierte',
        'support_reply'             => 'Auf Tickets antworten',
        'support_internal_notes'    => 'Interne Notizen lesen und schreiben',
        'support_assign'            => 'Tickets zuweisen',
        'support_close'             => 'Tickets schliessen und wieder oeffnen',
        'support_manage_categories' => 'Kategorien verwalten',
    ];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // admin bekommt alles; super_admin laeuft ohnehin ueber Gate::before
        if ($admin = Role::where('name', 'admin')->first()) {
            $admin->givePermissionTo(array_keys(self::PERMISSIONS));
        }

        $this->command?->info('Support permissions seeded: '.count(self::PERMISSIONS));
    }
}
