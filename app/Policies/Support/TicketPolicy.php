<?php

declare(strict_types=1);

namespace App\Policies\Support;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Support\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_support::ticket');
    }

    public function view(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('view_support::ticket');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support::ticket');
    }

    public function update(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('update_support::ticket');
    }

    public function delete(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('delete_support::ticket');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_support::ticket');
    }

    public function restore(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('restore_support::ticket');
    }

    public function forceDelete(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('force_delete_support::ticket');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_support::ticket');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_support::ticket');
    }

    public function replicate(AuthUser $authUser, Ticket $ticket): bool
    {
        return $authUser->can('replicate_support::ticket');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_support::ticket');
    }

}