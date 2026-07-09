<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerBan;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerBanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tracker::ban');
    }

    public function view(AuthUser $authUser, TrackerBan $trackerBan): bool
    {
        return $authUser->can('view_tracker::ban');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tracker::ban');
    }

    public function update(AuthUser $authUser, TrackerBan $trackerBan): bool
    {
        return $authUser->can('update_tracker::ban');
    }

    public function delete(AuthUser $authUser, TrackerBan $trackerBan): bool
    {
        return $authUser->can('delete_tracker::ban');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tracker::ban');
    }

    public function restore(AuthUser $authUser, TrackerBan $trackerBan): bool
    {
        return $authUser->can('restore_tracker::ban');
    }

    public function forceDelete(AuthUser $authUser, TrackerBan $trackerBan): bool
    {
        return $authUser->can('force_delete_tracker::ban');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tracker::ban');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tracker::ban');
    }

    public function replicate(AuthUser $authUser, TrackerBan $trackerBan): bool
    {
        return $authUser->can('replicate_tracker::ban');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tracker::ban');
    }

}