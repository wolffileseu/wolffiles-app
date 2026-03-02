<?php
namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncPermissions')
                ->label('🔄 Sync Permissions')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Permissions')
                ->modalDescription('Scans all Resources and Pages for new permissions and adds them automatically.')
                ->action(function () {
                    $created = 0;
                    $actions = ['view', 'create', 'update', 'delete'];
                    $existingNames = Permission::pluck('name')->toArray();

                    // Resources
                    $resources = glob(app_path('Filament/Resources/*Resource.php'));
                    foreach ($resources as $file) {
                        $class = basename($file, '.php');
                        $name = str_replace('Resource', '', $class);
                        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

                        foreach ($actions as $action) {
                            $permName = $action . '_' . $name;

                            // Prüfe ob diese oder eine Plural-Version schon existiert
                            if (in_array($permName, $existingNames)) continue;
                            if (in_array($permName . 's', $existingNames)) continue;
                            if (in_array($permName . 'es', $existingNames)) continue;
                            if (in_array(preg_replace('/y$/', 'ies', $permName), $existingNames)) continue;

                            // Prüfe auch umgekehrt: Singular existiert schon als Plural
                            $withoutS = rtrim($permName, 's');
                            if ($withoutS !== $permName && in_array($withoutS, $existingNames)) continue;

                            Permission::firstOrCreate([
                                'name' => $permName,
                                'guard_name' => 'web',
                            ]);
                            $created++;
                        }
                    }

                    // Pages
                    $pageMap = [
                        "FastDlMonitor" => "fastdl_monitor",
                        "TranslationManager" => "translation_manager",
                    ];
                    $pages = glob(app_path('Filament/Pages/*.php'));
                    foreach ($pages as $file) {
                        $class = basename($file, '.php');
                        if ($class === 'Dashboard') continue;
                        $name = $pageMap[$class] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
                        $permName = 'view_' . $name;

                        if (!in_array($permName, $existingNames)) {
                            Permission::firstOrCreate([
                                'name' => $permName,
                                'guard_name' => 'web',
                            ]);
                            $created++;
                        }
                    }

                    // Admin bekommt alle Permissions
                    $admin = Role::findByName('admin');
                    $admin->syncPermissions(Permission::all());

                    $total = Permission::count();

                    Notification::make()
                        ->title($created > 0 ? "{$created} neue Permissions gefunden!" : 'Alles aktuell!')
                        ->body("Gesamt: {$total} Permissions")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()->label('New Role'),
        ];
    }
}
