<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Testserver;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestserverPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_testserver');
    }

    public function view(AuthUser $authUser, Testserver $testserver): bool
    {
        return $authUser->can('view_testserver');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_testserver');
    }

    public function update(AuthUser $authUser, Testserver $testserver): bool
    {
        return $authUser->can('update_testserver');
    }

    public function delete(AuthUser $authUser, Testserver $testserver): bool
    {
        return $authUser->can('delete_testserver');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_testserver');
    }

    public function restore(AuthUser $authUser, Testserver $testserver): bool
    {
        return $authUser->can('restore_testserver');
    }

    public function forceDelete(AuthUser $authUser, Testserver $testserver): bool
    {
        return $authUser->can('force_delete_testserver');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_testserver');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_testserver');
    }

    public function replicate(AuthUser $authUser, Testserver $testserver): bool
    {
        return $authUser->can('replicate_testserver');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_testserver');
    }

}