<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Report;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_report');
    }

    public function view(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('view_report');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_report');
    }

    public function update(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('update_report');
    }

    public function delete(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('delete_report');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_report');
    }

    public function restore(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('restore_report');
    }

    public function forceDelete(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('force_delete_report');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_report');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_report');
    }

    public function replicate(AuthUser $authUser, Report $report): bool
    {
        return $authUser->can('replicate_report');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_report');
    }

}