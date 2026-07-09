<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Menu;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_menu');
    }

    public function view(AuthUser $authUser, Menu $menu): bool
    {
        return $authUser->can('view_menu');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_menu');
    }

    public function update(AuthUser $authUser, Menu $menu): bool
    {
        return $authUser->can('update_menu');
    }

    public function delete(AuthUser $authUser, Menu $menu): bool
    {
        return $authUser->can('delete_menu');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_menu');
    }

    public function restore(AuthUser $authUser, Menu $menu): bool
    {
        return $authUser->can('restore_menu');
    }

    public function forceDelete(AuthUser $authUser, Menu $menu): bool
    {
        return $authUser->can('force_delete_menu');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_menu');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_menu');
    }

    public function replicate(AuthUser $authUser, Menu $menu): bool
    {
        return $authUser->can('replicate_menu');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_menu');
    }

}