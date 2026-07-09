<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TutorialCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class TutorialCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tutorial::category');
    }

    public function view(AuthUser $authUser, TutorialCategory $tutorialCategory): bool
    {
        return $authUser->can('view_tutorial::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tutorial::category');
    }

    public function update(AuthUser $authUser, TutorialCategory $tutorialCategory): bool
    {
        return $authUser->can('update_tutorial::category');
    }

    public function delete(AuthUser $authUser, TutorialCategory $tutorialCategory): bool
    {
        return $authUser->can('delete_tutorial::category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tutorial::category');
    }

    public function restore(AuthUser $authUser, TutorialCategory $tutorialCategory): bool
    {
        return $authUser->can('restore_tutorial::category');
    }

    public function forceDelete(AuthUser $authUser, TutorialCategory $tutorialCategory): bool
    {
        return $authUser->can('force_delete_tutorial::category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tutorial::category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tutorial::category');
    }

    public function replicate(AuthUser $authUser, TutorialCategory $tutorialCategory): bool
    {
        return $authUser->can('replicate_tutorial::category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tutorial::category');
    }

}