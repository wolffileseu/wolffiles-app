<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EttvSlot;
use Illuminate\Auth\Access\HandlesAuthorization;

class EttvSlotPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_ettv::slot');
    }

    public function view(AuthUser $authUser, EttvSlot $ettvSlot): bool
    {
        return $authUser->can('view_ettv::slot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_ettv::slot');
    }

    public function update(AuthUser $authUser, EttvSlot $ettvSlot): bool
    {
        return $authUser->can('update_ettv::slot');
    }

    public function delete(AuthUser $authUser, EttvSlot $ettvSlot): bool
    {
        return $authUser->can('delete_ettv::slot');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_ettv::slot');
    }

    public function restore(AuthUser $authUser, EttvSlot $ettvSlot): bool
    {
        return $authUser->can('restore_ettv::slot');
    }

    public function forceDelete(AuthUser $authUser, EttvSlot $ettvSlot): bool
    {
        return $authUser->can('force_delete_ettv::slot');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_ettv::slot');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_ettv::slot');
    }

    public function replicate(AuthUser $authUser, EttvSlot $ettvSlot): bool
    {
        return $authUser->can('replicate_ettv::slot');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_ettv::slot');
    }

}