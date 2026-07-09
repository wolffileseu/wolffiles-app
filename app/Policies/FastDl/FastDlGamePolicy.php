<?php

declare(strict_types=1);

namespace App\Policies\FastDl;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FastDl\FastDlGame;
use Illuminate\Auth\Access\HandlesAuthorization;

class FastDlGamePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_fast::dl::game');
    }

    public function view(AuthUser $authUser, FastDlGame $fastDlGame): bool
    {
        return $authUser->can('view_fast::dl::game');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_fast::dl::game');
    }

    public function update(AuthUser $authUser, FastDlGame $fastDlGame): bool
    {
        return $authUser->can('update_fast::dl::game');
    }

    public function delete(AuthUser $authUser, FastDlGame $fastDlGame): bool
    {
        return $authUser->can('delete_fast::dl::game');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_fast::dl::game');
    }

    public function restore(AuthUser $authUser, FastDlGame $fastDlGame): bool
    {
        return $authUser->can('restore_fast::dl::game');
    }

    public function forceDelete(AuthUser $authUser, FastDlGame $fastDlGame): bool
    {
        return $authUser->can('force_delete_fast::dl::game');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_fast::dl::game');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_fast::dl::game');
    }

    public function replicate(AuthUser $authUser, FastDlGame $fastDlGame): bool
    {
        return $authUser->can('replicate_fast::dl::game');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_fast::dl::game');
    }

}