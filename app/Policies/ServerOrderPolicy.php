<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServerOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerOrderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_server::order');
    }

    public function view(AuthUser $authUser, ServerOrder $serverOrder): bool
    {
        return $authUser->can('view_server::order');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_server::order');
    }

    public function update(AuthUser $authUser, ServerOrder $serverOrder): bool
    {
        return $authUser->can('update_server::order');
    }

    public function delete(AuthUser $authUser, ServerOrder $serverOrder): bool
    {
        return $authUser->can('delete_server::order');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_server::order');
    }

    public function restore(AuthUser $authUser, ServerOrder $serverOrder): bool
    {
        return $authUser->can('restore_server::order');
    }

    public function forceDelete(AuthUser $authUser, ServerOrder $serverOrder): bool
    {
        return $authUser->can('force_delete_server::order');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_server::order');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_server::order');
    }

    public function replicate(AuthUser $authUser, ServerOrder $serverOrder): bool
    {
        return $authUser->can('replicate_server::order');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_server::order');
    }

}