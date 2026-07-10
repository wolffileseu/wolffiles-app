<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SocialMediaChannel;
use Illuminate\Auth\Access\HandlesAuthorization;

class SocialMediaChannelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_social::media::channel');
    }

    public function view(AuthUser $authUser, SocialMediaChannel $socialMediaChannel): bool
    {
        return $authUser->can('view_social::media::channel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_social::media::channel');
    }

    public function update(AuthUser $authUser, SocialMediaChannel $socialMediaChannel): bool
    {
        return $authUser->can('update_social::media::channel');
    }

    public function delete(AuthUser $authUser, SocialMediaChannel $socialMediaChannel): bool
    {
        return $authUser->can('delete_social::media::channel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_social::media::channel');
    }

    public function restore(AuthUser $authUser, SocialMediaChannel $socialMediaChannel): bool
    {
        return $authUser->can('restore_social::media::channel');
    }

    public function forceDelete(AuthUser $authUser, SocialMediaChannel $socialMediaChannel): bool
    {
        return $authUser->can('force_delete_social::media::channel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_social::media::channel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_social::media::channel');
    }

    public function replicate(AuthUser $authUser, SocialMediaChannel $socialMediaChannel): bool
    {
        return $authUser->can('replicate_social::media::channel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_social::media::channel');
    }

}