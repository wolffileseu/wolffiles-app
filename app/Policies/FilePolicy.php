<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\File;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_file');
    }

    public function view(AuthUser $authUser, File $file): bool
    {
        return $authUser->can('view_file');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_file');
    }

    public function update(AuthUser $authUser, File $file): bool
    {
        return $authUser->can('update_file');
    }

    public function delete(AuthUser $authUser, File $file): bool
    {
        return $authUser->can('delete_file');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_file');
    }

    public function restore(AuthUser $authUser, File $file): bool
    {
        return $authUser->can('restore_file');
    }

    public function forceDelete(AuthUser $authUser, File $file): bool
    {
        return $authUser->can('force_delete_file');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_file');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_file');
    }

    public function replicate(AuthUser $authUser, File $file): bool
    {
        return $authUser->can('replicate_file');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_file');
    }

}