<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServerProduct;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerProductPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_server::product');
    }

    public function view(AuthUser $authUser, ServerProduct $serverProduct): bool
    {
        return $authUser->can('view_server::product');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_server::product');
    }

    public function update(AuthUser $authUser, ServerProduct $serverProduct): bool
    {
        return $authUser->can('update_server::product');
    }

    public function delete(AuthUser $authUser, ServerProduct $serverProduct): bool
    {
        return $authUser->can('delete_server::product');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_server::product');
    }

    public function restore(AuthUser $authUser, ServerProduct $serverProduct): bool
    {
        return $authUser->can('restore_server::product');
    }

    public function forceDelete(AuthUser $authUser, ServerProduct $serverProduct): bool
    {
        return $authUser->can('force_delete_server::product');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_server::product');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_server::product');
    }

    public function replicate(AuthUser $authUser, ServerProduct $serverProduct): bool
    {
        return $authUser->can('replicate_server::product');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_server::product');
    }

}