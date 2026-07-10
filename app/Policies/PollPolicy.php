<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Poll;
use Illuminate\Auth\Access\HandlesAuthorization;

class PollPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_poll');
    }

    public function view(AuthUser $authUser, Poll $poll): bool
    {
        return $authUser->can('view_poll');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_poll');
    }

    public function update(AuthUser $authUser, Poll $poll): bool
    {
        return $authUser->can('update_poll');
    }

    public function delete(AuthUser $authUser, Poll $poll): bool
    {
        return $authUser->can('delete_poll');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_poll');
    }

    public function restore(AuthUser $authUser, Poll $poll): bool
    {
        return $authUser->can('restore_poll');
    }

    public function forceDelete(AuthUser $authUser, Poll $poll): bool
    {
        return $authUser->can('force_delete_poll');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_poll');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_poll');
    }

    public function replicate(AuthUser $authUser, Poll $poll): bool
    {
        return $authUser->can('replicate_poll');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_poll');
    }

}