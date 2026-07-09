<?php

declare(strict_types=1);

namespace App\Policies\FastDl;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FastDl\FastDlFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class FastDlFilePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_fast::dl::file');
    }

    public function view(AuthUser $authUser, FastDlFile $fastDlFile): bool
    {
        return $authUser->can('view_fast::dl::file');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_fast::dl::file');
    }

    public function update(AuthUser $authUser, FastDlFile $fastDlFile): bool
    {
        return $authUser->can('update_fast::dl::file');
    }

    public function delete(AuthUser $authUser, FastDlFile $fastDlFile): bool
    {
        return $authUser->can('delete_fast::dl::file');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_fast::dl::file');
    }

    public function restore(AuthUser $authUser, FastDlFile $fastDlFile): bool
    {
        return $authUser->can('restore_fast::dl::file');
    }

    public function forceDelete(AuthUser $authUser, FastDlFile $fastDlFile): bool
    {
        return $authUser->can('force_delete_fast::dl::file');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_fast::dl::file');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_fast::dl::file');
    }

    public function replicate(AuthUser $authUser, FastDlFile $fastDlFile): bool
    {
        return $authUser->can('replicate_fast::dl::file');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_fast::dl::file');
    }

}