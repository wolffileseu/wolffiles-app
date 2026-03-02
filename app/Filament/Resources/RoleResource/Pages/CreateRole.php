<?php
namespace App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    private function syncPermissions(): void
    {
        $allPerms = [];
        foreach ($this->data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $allPerms = array_merge($allPerms, $value);
            }
        }
        /** @var \Spatie\Permission\Models\Role $record */
        $record = $this->record;
        $record->syncPermissions($allPerms);
    }
}
