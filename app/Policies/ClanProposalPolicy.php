<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ClanProposal;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClanProposalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_clan::proposal');
    }

    public function view(AuthUser $authUser, ClanProposal $clanProposal): bool
    {
        return $authUser->can('view_clan::proposal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_clan::proposal');
    }

    public function update(AuthUser $authUser, ClanProposal $clanProposal): bool
    {
        return $authUser->can('update_clan::proposal');
    }

    public function delete(AuthUser $authUser, ClanProposal $clanProposal): bool
    {
        return $authUser->can('delete_clan::proposal');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_clan::proposal');
    }

    public function restore(AuthUser $authUser, ClanProposal $clanProposal): bool
    {
        return $authUser->can('restore_clan::proposal');
    }

    public function forceDelete(AuthUser $authUser, ClanProposal $clanProposal): bool
    {
        return $authUser->can('force_delete_clan::proposal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_clan::proposal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_clan::proposal');
    }

    public function replicate(AuthUser $authUser, ClanProposal $clanProposal): bool
    {
        return $authUser->can('replicate_clan::proposal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_clan::proposal');
    }

}