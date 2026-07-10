<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerPlayer;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerPlayerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tracker::player');
    }

    public function view(AuthUser $authUser, TrackerPlayer $trackerPlayer): bool
    {
        return $authUser->can('view_tracker::player');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tracker::player');
    }

    public function update(AuthUser $authUser, TrackerPlayer $trackerPlayer): bool
    {
        return $authUser->can('update_tracker::player');
    }

    public function delete(AuthUser $authUser, TrackerPlayer $trackerPlayer): bool
    {
        return $authUser->can('delete_tracker::player');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tracker::player');
    }

    public function restore(AuthUser $authUser, TrackerPlayer $trackerPlayer): bool
    {
        return $authUser->can('restore_tracker::player');
    }

    public function forceDelete(AuthUser $authUser, TrackerPlayer $trackerPlayer): bool
    {
        return $authUser->can('force_delete_tracker::player');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tracker::player');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tracker::player');
    }

    public function replicate(AuthUser $authUser, TrackerPlayer $trackerPlayer): bool
    {
        return $authUser->can('replicate_tracker::player');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tracker::player');
    }

}