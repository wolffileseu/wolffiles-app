<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ClanApiKey;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClanApiKeyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_clan::api::key');
    }

    public function view(AuthUser $authUser, ClanApiKey $clanApiKey): bool
    {
        return $authUser->can('view_clan::api::key');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_clan::api::key');
    }

    public function update(AuthUser $authUser, ClanApiKey $clanApiKey): bool
    {
        return $authUser->can('update_clan::api::key');
    }

    public function delete(AuthUser $authUser, ClanApiKey $clanApiKey): bool
    {
        return $authUser->can('delete_clan::api::key');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_clan::api::key');
    }

    public function restore(AuthUser $authUser, ClanApiKey $clanApiKey): bool
    {
        return $authUser->can('restore_clan::api::key');
    }

    public function forceDelete(AuthUser $authUser, ClanApiKey $clanApiKey): bool
    {
        return $authUser->can('force_delete_clan::api::key');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_clan::api::key');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_clan::api::key');
    }

    public function replicate(AuthUser $authUser, ClanApiKey $clanApiKey): bool
    {
        return $authUser->can('replicate_clan::api::key');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_clan::api::key');
    }

}