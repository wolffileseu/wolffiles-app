<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProfileField;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileFieldPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_profile::field');
    }

    public function view(AuthUser $authUser, ProfileField $profileField): bool
    {
        return $authUser->can('view_profile::field');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_profile::field');
    }

    public function update(AuthUser $authUser, ProfileField $profileField): bool
    {
        return $authUser->can('update_profile::field');
    }

    public function delete(AuthUser $authUser, ProfileField $profileField): bool
    {
        return $authUser->can('delete_profile::field');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_profile::field');
    }

    public function restore(AuthUser $authUser, ProfileField $profileField): bool
    {
        return $authUser->can('restore_profile::field');
    }

    public function forceDelete(AuthUser $authUser, ProfileField $profileField): bool
    {
        return $authUser->can('force_delete_profile::field');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_profile::field');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_profile::field');
    }

    public function replicate(AuthUser $authUser, ProfileField $profileField): bool
    {
        return $authUser->can('replicate_profile::field');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_profile::field');
    }

}