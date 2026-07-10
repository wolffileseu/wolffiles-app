<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TestserverMod;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestserverModPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_testserver::mod');
    }

    public function view(AuthUser $authUser, TestserverMod $testserverMod): bool
    {
        return $authUser->can('view_testserver::mod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_testserver::mod');
    }

    public function update(AuthUser $authUser, TestserverMod $testserverMod): bool
    {
        return $authUser->can('update_testserver::mod');
    }

    public function delete(AuthUser $authUser, TestserverMod $testserverMod): bool
    {
        return $authUser->can('delete_testserver::mod');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_testserver::mod');
    }

    public function restore(AuthUser $authUser, TestserverMod $testserverMod): bool
    {
        return $authUser->can('restore_testserver::mod');
    }

    public function forceDelete(AuthUser $authUser, TestserverMod $testserverMod): bool
    {
        return $authUser->can('force_delete_testserver::mod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_testserver::mod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_testserver::mod');
    }

    public function replicate(AuthUser $authUser, TestserverMod $testserverMod): bool
    {
        return $authUser->can('replicate_testserver::mod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_testserver::mod');
    }

}