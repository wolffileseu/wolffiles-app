<?php

declare(strict_types=1);

namespace App\Policies\Tracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tracker\TrackerPlayerReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackerPlayerReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_player::report');
    }

    public function view(AuthUser $authUser, TrackerPlayerReport $trackerPlayerReport): bool
    {
        return $authUser->can('view_player::report');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_player::report');
    }

    public function update(AuthUser $authUser, TrackerPlayerReport $trackerPlayerReport): bool
    {
        return $authUser->can('update_player::report');
    }

    public function delete(AuthUser $authUser, TrackerPlayerReport $trackerPlayerReport): bool
    {
        return $authUser->can('delete_player::report');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_player::report');
    }

    public function restore(AuthUser $authUser, TrackerPlayerReport $trackerPlayerReport): bool
    {
        return $authUser->can('restore_player::report');
    }

    public function forceDelete(AuthUser $authUser, TrackerPlayerReport $trackerPlayerReport): bool
    {
        return $authUser->can('force_delete_player::report');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_player::report');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_player::report');
    }

    public function replicate(AuthUser $authUser, TrackerPlayerReport $trackerPlayerReport): bool
    {
        return $authUser->can('replicate_player::report');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_player::report');
    }

}