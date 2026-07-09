<?php

declare(strict_types=1);

namespace App\Policies\Pm;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pm\PmConversation;
use Illuminate\Auth\Access\HandlesAuthorization;

class PmConversationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_pm::conversation');
    }

    public function view(AuthUser $authUser, PmConversation $pmConversation): bool
    {
        return $authUser->can('view_pm::conversation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_pm::conversation');
    }

    public function update(AuthUser $authUser, PmConversation $pmConversation): bool
    {
        return $authUser->can('update_pm::conversation');
    }

    public function delete(AuthUser $authUser, PmConversation $pmConversation): bool
    {
        return $authUser->can('delete_pm::conversation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_pm::conversation');
    }

    public function restore(AuthUser $authUser, PmConversation $pmConversation): bool
    {
        return $authUser->can('restore_pm::conversation');
    }

    public function forceDelete(AuthUser $authUser, PmConversation $pmConversation): bool
    {
        return $authUser->can('force_delete_pm::conversation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_pm::conversation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_pm::conversation');
    }

    public function replicate(AuthUser $authUser, PmConversation $pmConversation): bool
    {
        return $authUser->can('replicate_pm::conversation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_pm::conversation');
    }

}