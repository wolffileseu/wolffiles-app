<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerGame;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerGamePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tracker::game');
    }

    public function view(AuthUser $authUser, TrackerGame $trackerGame): bool
    {
        return $authUser->can('view_tracker::game');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tracker::game');
    }

    public function update(AuthUser $authUser, TrackerGame $trackerGame): bool
    {
        return $authUser->can('update_tracker::game');
    }

    public function delete(AuthUser $authUser, TrackerGame $trackerGame): bool
    {
        return $authUser->can('delete_tracker::game');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tracker::game');
    }

    public function restore(AuthUser $authUser, TrackerGame $trackerGame): bool
    {
        return $authUser->can('restore_tracker::game');
    }

    public function forceDelete(AuthUser $authUser, TrackerGame $trackerGame): bool
    {
        return $authUser->can('force_delete_tracker::game');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tracker::game');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tracker::game');
    }

    public function replicate(AuthUser $authUser, TrackerGame $trackerGame): bool
    {
        return $authUser->can('replicate_tracker::game');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tracker::game');
    }

}