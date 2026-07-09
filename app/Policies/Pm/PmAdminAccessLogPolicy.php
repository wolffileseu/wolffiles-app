<?php

declare(strict_types=1);

namespace App\Policies\Pm;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pm\PmAdminAccessLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class PmAdminAccessLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_pm::admin::access::log');
    }

    public function view(AuthUser $authUser, PmAdminAccessLog $pmAdminAccessLog): bool
    {
        return $authUser->can('view_pm::admin::access::log');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_pm::admin::access::log');
    }

    public function update(AuthUser $authUser, PmAdminAccessLog $pmAdminAccessLog): bool
    {
        return $authUser->can('update_pm::admin::access::log');
    }

    public function delete(AuthUser $authUser, PmAdminAccessLog $pmAdminAccessLog): bool
    {
        return $authUser->can('delete_pm::admin::access::log');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_pm::admin::access::log');
    }

    public function restore(AuthUser $authUser, PmAdminAccessLog $pmAdminAccessLog): bool
    {
        return $authUser->can('restore_pm::admin::access::log');
    }

    public function forceDelete(AuthUser $authUser, PmAdminAccessLog $pmAdminAccessLog): bool
    {
        return $authUser->can('force_delete_pm::admin::access::log');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_pm::admin::access::log');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_pm::admin::access::log');
    }

    public function replicate(AuthUser $authUser, PmAdminAccessLog $pmAdminAccessLog): bool
    {
        return $authUser->can('replicate_pm::admin::access::log');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_pm::admin::access::log');
    }

}