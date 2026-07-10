<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServerInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerInvoicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_server::invoice');
    }

    public function view(AuthUser $authUser, ServerInvoice $serverInvoice): bool
    {
        return $authUser->can('view_server::invoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_server::invoice');
    }

    public function update(AuthUser $authUser, ServerInvoice $serverInvoice): bool
    {
        return $authUser->can('update_server::invoice');
    }

    public function delete(AuthUser $authUser, ServerInvoice $serverInvoice): bool
    {
        return $authUser->can('delete_server::invoice');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_server::invoice');
    }

    public function restore(AuthUser $authUser, ServerInvoice $serverInvoice): bool
    {
        return $authUser->can('restore_server::invoice');
    }

    public function forceDelete(AuthUser $authUser, ServerInvoice $serverInvoice): bool
    {
        return $authUser->can('force_delete_server::invoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_server::invoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_server::invoice');
    }

    public function replicate(AuthUser $authUser, ServerInvoice $serverInvoice): bool
    {
        return $authUser->can('replicate_server::invoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_server::invoice');
    }

}