<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tutorial;
use Illuminate\Auth\Access\HandlesAuthorization;

class TutorialPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tutorial');
    }

    public function view(AuthUser $authUser, Tutorial $tutorial): bool
    {
        return $authUser->can('view_tutorial');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tutorial');
    }

    public function update(AuthUser $authUser, Tutorial $tutorial): bool
    {
        return $authUser->can('update_tutorial');
    }

    public function delete(AuthUser $authUser, Tutorial $tutorial): bool
    {
        return $authUser->can('delete_tutorial');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tutorial');
    }

    public function restore(AuthUser $authUser, Tutorial $tutorial): bool
    {
        return $authUser->can('restore_tutorial');
    }

    public function forceDelete(AuthUser $authUser, Tutorial $tutorial): bool
    {
        return $authUser->can('force_delete_tutorial');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tutorial');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tutorial');
    }

    public function replicate(AuthUser $authUser, Tutorial $tutorial): bool
    {
        return $authUser->can('replicate_tutorial');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tutorial');
    }

}