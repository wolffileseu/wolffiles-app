<?php

declare(strict_types=1);

namespace App\Policies\FastDl;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FastDl\FastDlClan;
use Illuminate\Auth\Access\HandlesAuthorization;

class FastDlClanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_fast::dl::clan');
    }

    public function view(AuthUser $authUser, FastDlClan $fastDlClan): bool
    {
        return $authUser->can('view_fast::dl::clan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_fast::dl::clan');
    }

    public function update(AuthUser $authUser, FastDlClan $fastDlClan): bool
    {
        return $authUser->can('update_fast::dl::clan');
    }

    public function delete(AuthUser $authUser, FastDlClan $fastDlClan): bool
    {
        return $authUser->can('delete_fast::dl::clan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_fast::dl::clan');
    }

    public function restore(AuthUser $authUser, FastDlClan $fastDlClan): bool
    {
        return $authUser->can('restore_fast::dl::clan');
    }

    public function forceDelete(AuthUser $authUser, FastDlClan $fastDlClan): bool
    {
        return $authUser->can('force_delete_fast::dl::clan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_fast::dl::clan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_fast::dl::clan');
    }

    public function replicate(AuthUser $authUser, FastDlClan $fastDlClan): bool
    {
        return $authUser->can('replicate_fast::dl::clan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_fast::dl::clan');
    }

}