<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TestserverSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestserverSessionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_testserver::session');
    }

    public function view(AuthUser $authUser, TestserverSession $testserverSession): bool
    {
        return $authUser->can('view_testserver::session');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_testserver::session');
    }

    public function update(AuthUser $authUser, TestserverSession $testserverSession): bool
    {
        return $authUser->can('update_testserver::session');
    }

    public function delete(AuthUser $authUser, TestserverSession $testserverSession): bool
    {
        return $authUser->can('delete_testserver::session');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_testserver::session');
    }

    public function restore(AuthUser $authUser, TestserverSession $testserverSession): bool
    {
        return $authUser->can('restore_testserver::session');
    }

    public function forceDelete(AuthUser $authUser, TestserverSession $testserverSession): bool
    {
        return $authUser->can('force_delete_testserver::session');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_testserver::session');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_testserver::session');
    }

    public function replicate(AuthUser $authUser, TestserverSession $testserverSession): bool
    {
        return $authUser->can('replicate_testserver::session');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_testserver::session');
    }

}