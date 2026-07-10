<?php

declare(strict_types=1);

namespace App\Policies\Pm;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pm\PmMessageReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class PmMessageReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_pm::message::report');
    }

    public function view(AuthUser $authUser, PmMessageReport $pmMessageReport): bool
    {
        return $authUser->can('view_pm::message::report');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_pm::message::report');
    }

    public function update(AuthUser $authUser, PmMessageReport $pmMessageReport): bool
    {
        return $authUser->can('update_pm::message::report');
    }

    public function delete(AuthUser $authUser, PmMessageReport $pmMessageReport): bool
    {
        return $authUser->can('delete_pm::message::report');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_pm::message::report');
    }

    public function restore(AuthUser $authUser, PmMessageReport $pmMessageReport): bool
    {
        return $authUser->can('restore_pm::message::report');
    }

    public function forceDelete(AuthUser $authUser, PmMessageReport $pmMessageReport): bool
    {
        return $authUser->can('force_delete_pm::message::report');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_pm::message::report');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_pm::message::report');
    }

    public function replicate(AuthUser $authUser, PmMessageReport $pmMessageReport): bool
    {
        return $authUser->can('replicate_pm::message::report');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_pm::message::report');
    }

}