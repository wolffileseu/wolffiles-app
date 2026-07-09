<?php

declare(strict_types=1);

namespace App\Policies\FastDl;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FastDl\FastDlDirectory;
use Illuminate\Auth\Access\HandlesAuthorization;

class FastDlDirectoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_fast::dl::directory');
    }

    public function view(AuthUser $authUser, FastDlDirectory $fastDlDirectory): bool
    {
        return $authUser->can('view_fast::dl::directory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_fast::dl::directory');
    }

    public function update(AuthUser $authUser, FastDlDirectory $fastDlDirectory): bool
    {
        return $authUser->can('update_fast::dl::directory');
    }

    public function delete(AuthUser $authUser, FastDlDirectory $fastDlDirectory): bool
    {
        return $authUser->can('delete_fast::dl::directory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_fast::dl::directory');
    }

    public function restore(AuthUser $authUser, FastDlDirectory $fastDlDirectory): bool
    {
        return $authUser->can('restore_fast::dl::directory');
    }

    public function forceDelete(AuthUser $authUser, FastDlDirectory $fastDlDirectory): bool
    {
        return $authUser->can('force_delete_fast::dl::directory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_fast::dl::directory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_fast::dl::directory');
    }

    public function replicate(AuthUser $authUser, FastDlDirectory $fastDlDirectory): bool
    {
        return $authUser->can('replicate_fast::dl::directory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_fast::dl::directory');
    }

}