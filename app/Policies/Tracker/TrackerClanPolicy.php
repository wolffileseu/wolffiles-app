<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerClan;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerClanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tracker::clan');
    }

    public function view(AuthUser $authUser, TrackerClan $trackerClan): bool
    {
        return $authUser->can('view_tracker::clan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tracker::clan');
    }

    public function update(AuthUser $authUser, TrackerClan $trackerClan): bool
    {
        return $authUser->can('update_tracker::clan');
    }

    public function delete(AuthUser $authUser, TrackerClan $trackerClan): bool
    {
        return $authUser->can('delete_tracker::clan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tracker::clan');
    }

    public function restore(AuthUser $authUser, TrackerClan $trackerClan): bool
    {
        return $authUser->can('restore_tracker::clan');
    }

    public function forceDelete(AuthUser $authUser, TrackerClan $trackerClan): bool
    {
        return $authUser->can('force_delete_tracker::clan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tracker::clan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tracker::clan');
    }

    public function replicate(AuthUser $authUser, TrackerClan $trackerClan): bool
    {
        return $authUser->can('replicate_tracker::clan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tracker::clan');
    }

}