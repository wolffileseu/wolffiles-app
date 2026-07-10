<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Demo;
use Illuminate\Auth\Access\HandlesAuthorization;

class DemoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_demo');
    }

    public function view(AuthUser $authUser, Demo $demo): bool
    {
        return $authUser->can('view_demo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_demo');
    }

    public function update(AuthUser $authUser, Demo $demo): bool
    {
        return $authUser->can('update_demo');
    }

    public function delete(AuthUser $authUser, Demo $demo): bool
    {
        return $authUser->can('delete_demo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_demo');
    }

    public function restore(AuthUser $authUser, Demo $demo): bool
    {
        return $authUser->can('restore_demo');
    }

    public function forceDelete(AuthUser $authUser, Demo $demo): bool
    {
        return $authUser->can('force_delete_demo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_demo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_demo');
    }

    public function replicate(AuthUser $authUser, Demo $demo): bool
    {
        return $authUser->can('replicate_demo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_demo');
    }

}