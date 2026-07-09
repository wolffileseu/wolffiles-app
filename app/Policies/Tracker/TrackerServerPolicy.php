<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerServer;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerServerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tracker::server');
    }

    public function view(AuthUser $authUser, TrackerServer $trackerServer): bool
    {
        return $authUser->can('view_tracker::server');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tracker::server');
    }

    public function update(AuthUser $authUser, TrackerServer $trackerServer): bool
    {
        return $authUser->can('update_tracker::server');
    }

    public function delete(AuthUser $authUser, TrackerServer $trackerServer): bool
    {
        return $authUser->can('delete_tracker::server');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tracker::server');
    }

    public function restore(AuthUser $authUser, TrackerServer $trackerServer): bool
    {
        return $authUser->can('restore_tracker::server');
    }

    public function forceDelete(AuthUser $authUser, TrackerServer $trackerServer): bool
    {
        return $authUser->can('force_delete_tracker::server');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tracker::server');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tracker::server');
    }

    public function replicate(AuthUser $authUser, TrackerServer $trackerServer): bool
    {
        return $authUser->can('replicate_tracker::server');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tracker::server');
    }

}