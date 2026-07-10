<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Badge;
use Illuminate\Auth\Access\HandlesAuthorization;

class BadgePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_badge');
    }

    public function view(AuthUser $authUser, Badge $badge): bool
    {
        return $authUser->can('view_badge');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_badge');
    }

    public function update(AuthUser $authUser, Badge $badge): bool
    {
        return $authUser->can('update_badge');
    }

    public function delete(AuthUser $authUser, Badge $badge): bool
    {
        return $authUser->can('delete_badge');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_badge');
    }

    public function restore(AuthUser $authUser, Badge $badge): bool
    {
        return $authUser->can('restore_badge');
    }

    public function forceDelete(AuthUser $authUser, Badge $badge): bool
    {
        return $authUser->can('force_delete_badge');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_badge');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_badge');
    }

    public function replicate(AuthUser $authUser, Badge $badge): bool
    {
        return $authUser->can('replicate_badge');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_badge');
    }

}