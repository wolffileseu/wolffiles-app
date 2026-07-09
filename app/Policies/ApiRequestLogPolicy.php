<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ApiRequestLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiRequestLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_api::request::log');
    }

    public function view(AuthUser $authUser, ApiRequestLog $apiRequestLog): bool
    {
        return $authUser->can('view_api::request::log');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_api::request::log');
    }

    public function update(AuthUser $authUser, ApiRequestLog $apiRequestLog): bool
    {
        return $authUser->can('update_api::request::log');
    }

    public function delete(AuthUser $authUser, ApiRequestLog $apiRequestLog): bool
    {
        return $authUser->can('delete_api::request::log');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_api::request::log');
    }

    public function restore(AuthUser $authUser, ApiRequestLog $apiRequestLog): bool
    {
        return $authUser->can('restore_api::request::log');
    }

    public function forceDelete(AuthUser $authUser, ApiRequestLog $apiRequestLog): bool
    {
        return $authUser->can('force_delete_api::request::log');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_api::request::log');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_api::request::log');
    }

    public function replicate(AuthUser $authUser, ApiRequestLog $apiRequestLog): bool
    {
        return $authUser->can('replicate_api::request::log');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_api::request::log');
    }

}