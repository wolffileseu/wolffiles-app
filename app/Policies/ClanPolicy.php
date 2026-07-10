<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Clan;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_clan');
    }

    public function view(AuthUser $authUser, Clan $clan): bool
    {
        return $authUser->can('view_clan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_clan');
    }

    public function update(AuthUser $authUser, Clan $clan): bool
    {
        return $authUser->can('update_clan');
    }

    public function delete(AuthUser $authUser, Clan $clan): bool
    {
        return $authUser->can('delete_clan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_clan');
    }

    public function restore(AuthUser $authUser, Clan $clan): bool
    {
        return $authUser->can('restore_clan');
    }

    public function forceDelete(AuthUser $authUser, Clan $clan): bool
    {
        return $authUser->can('force_delete_clan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_clan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_clan');
    }

    public function replicate(AuthUser $authUser, Clan $clan): bool
    {
        return $authUser->can('replicate_clan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_clan');
    }

}