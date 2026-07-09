<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerClaim;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerClaimPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tracker::claim');
    }

    public function view(AuthUser $authUser, TrackerClaim $trackerClaim): bool
    {
        return $authUser->can('view_tracker::claim');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tracker::claim');
    }

    public function update(AuthUser $authUser, TrackerClaim $trackerClaim): bool
    {
        return $authUser->can('update_tracker::claim');
    }

    public function delete(AuthUser $authUser, TrackerClaim $trackerClaim): bool
    {
        return $authUser->can('delete_tracker::claim');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tracker::claim');
    }

    public function restore(AuthUser $authUser, TrackerClaim $trackerClaim): bool
    {
        return $authUser->can('restore_tracker::claim');
    }

    public function forceDelete(AuthUser $authUser, TrackerClaim $trackerClaim): bool
    {
        return $authUser->can('force_delete_tracker::claim');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tracker::claim');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tracker::claim');
    }

    public function replicate(AuthUser $authUser, TrackerClaim $trackerClaim): bool
    {
        return $authUser->can('replicate_tracker::claim');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tracker::claim');
    }

}