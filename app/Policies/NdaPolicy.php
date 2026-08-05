<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Nda;
use Illuminate\Auth\Access\HandlesAuthorization;

class NdaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_nda');
    }

    public function view(AuthUser $authUser, Nda $nda): bool
    {
        return $authUser->can('view_nda');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_nda');
    }

    public function update(AuthUser $authUser, Nda $nda): bool
    {
        return $authUser->can('update_nda');
    }

    public function delete(AuthUser $authUser, Nda $nda): bool
    {
        return $authUser->can('delete_nda');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_nda');
    }

    public function restore(AuthUser $authUser, Nda $nda): bool
    {
        return $authUser->can('restore_nda');
    }

    public function forceDelete(AuthUser $authUser, Nda $nda): bool
    {
        return $authUser->can('force_delete_nda');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_nda');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_nda');
    }

    public function replicate(AuthUser $authUser, Nda $nda): bool
    {
        return $authUser->can('replicate_nda');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_nda');
    }

}