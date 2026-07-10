<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MissingTexture;
use Illuminate\Auth\Access\HandlesAuthorization;

class MissingTexturePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_missing::texture');
    }

    public function view(AuthUser $authUser, MissingTexture $missingTexture): bool
    {
        return $authUser->can('view_missing::texture');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_missing::texture');
    }

    public function update(AuthUser $authUser, MissingTexture $missingTexture): bool
    {
        return $authUser->can('update_missing::texture');
    }

    public function delete(AuthUser $authUser, MissingTexture $missingTexture): bool
    {
        return $authUser->can('delete_missing::texture');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_missing::texture');
    }

    public function restore(AuthUser $authUser, MissingTexture $missingTexture): bool
    {
        return $authUser->can('restore_missing::texture');
    }

    public function forceDelete(AuthUser $authUser, MissingTexture $missingTexture): bool
    {
        return $authUser->can('force_delete_missing::texture');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_missing::texture');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_missing::texture');
    }

    public function replicate(AuthUser $authUser, MissingTexture $missingTexture): bool
    {
        return $authUser->can('replicate_missing::texture');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_missing::texture');
    }

}