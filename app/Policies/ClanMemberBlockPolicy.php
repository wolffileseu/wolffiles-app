<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ClanMemberBlock;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClanMemberBlockPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_clan::member::block');
    }

    public function view(AuthUser $authUser, ClanMemberBlock $clanMemberBlock): bool
    {
        return $authUser->can('view_clan::member::block');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_clan::member::block');
    }

    public function update(AuthUser $authUser, ClanMemberBlock $clanMemberBlock): bool
    {
        return $authUser->can('update_clan::member::block');
    }

    public function delete(AuthUser $authUser, ClanMemberBlock $clanMemberBlock): bool
    {
        return $authUser->can('delete_clan::member::block');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_clan::member::block');
    }

    public function restore(AuthUser $authUser, ClanMemberBlock $clanMemberBlock): bool
    {
        return $authUser->can('restore_clan::member::block');
    }

    public function forceDelete(AuthUser $authUser, ClanMemberBlock $clanMemberBlock): bool
    {
        return $authUser->can('force_delete_clan::member::block');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_clan::member::block');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_clan::member::block');
    }

    public function replicate(AuthUser $authUser, ClanMemberBlock $clanMemberBlock): bool
    {
        return $authUser->can('replicate_clan::member::block');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_clan::member::block');
    }

}