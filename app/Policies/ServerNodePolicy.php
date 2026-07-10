<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServerNode;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerNodePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_server::node');
    }

    public function view(AuthUser $authUser, ServerNode $serverNode): bool
    {
        return $authUser->can('view_server::node');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_server::node');
    }

    public function update(AuthUser $authUser, ServerNode $serverNode): bool
    {
        return $authUser->can('update_server::node');
    }

    public function delete(AuthUser $authUser, ServerNode $serverNode): bool
    {
        return $authUser->can('delete_server::node');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_server::node');
    }

    public function restore(AuthUser $authUser, ServerNode $serverNode): bool
    {
        return $authUser->can('restore_server::node');
    }

    public function forceDelete(AuthUser $authUser, ServerNode $serverNode): bool
    {
        return $authUser->can('force_delete_server::node');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_server::node');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_server::node');
    }

    public function replicate(AuthUser $authUser, ServerNode $serverNode): bool
    {
        return $authUser->can('replicate_server::node');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_server::node');
    }

}