<?php

declare(strict_types=1);

namespace App\Policies\Support;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Support\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_support::category');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('view_support::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_support::category');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('update_support::category');
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('delete_support::category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_support::category');
    }

    public function restore(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('restore_support::category');
    }

    public function forceDelete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('force_delete_support::category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_support::category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_support::category');
    }

    public function replicate(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('replicate_support::category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_support::category');
    }

}