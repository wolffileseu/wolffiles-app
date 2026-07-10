<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PartnerLink;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartnerLinkPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_partner::link');
    }

    public function view(AuthUser $authUser, PartnerLink $partnerLink): bool
    {
        return $authUser->can('view_partner::link');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_partner::link');
    }

    public function update(AuthUser $authUser, PartnerLink $partnerLink): bool
    {
        return $authUser->can('update_partner::link');
    }

    public function delete(AuthUser $authUser, PartnerLink $partnerLink): bool
    {
        return $authUser->can('delete_partner::link');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_partner::link');
    }

    public function restore(AuthUser $authUser, PartnerLink $partnerLink): bool
    {
        return $authUser->can('restore_partner::link');
    }

    public function forceDelete(AuthUser $authUser, PartnerLink $partnerLink): bool
    {
        return $authUser->can('force_delete_partner::link');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_partner::link');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_partner::link');
    }

    public function replicate(AuthUser $authUser, PartnerLink $partnerLink): bool
    {
        return $authUser->can('replicate_partner::link');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_partner::link');
    }

}