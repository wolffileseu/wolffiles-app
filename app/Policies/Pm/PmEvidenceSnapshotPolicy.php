<?php

declare(strict_types=1);

namespace App\Policies\Pm;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pm\PmEvidenceSnapshot;
use Illuminate\Auth\Access\HandlesAuthorization;

class PmEvidenceSnapshotPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_pm::evidence::snapshot');
    }

    public function view(AuthUser $authUser, PmEvidenceSnapshot $pmEvidenceSnapshot): bool
    {
        return $authUser->can('view_pm::evidence::snapshot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_pm::evidence::snapshot');
    }

    public function update(AuthUser $authUser, PmEvidenceSnapshot $pmEvidenceSnapshot): bool
    {
        return $authUser->can('update_pm::evidence::snapshot');
    }

    public function delete(AuthUser $authUser, PmEvidenceSnapshot $pmEvidenceSnapshot): bool
    {
        return $authUser->can('delete_pm::evidence::snapshot');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_pm::evidence::snapshot');
    }

    public function restore(AuthUser $authUser, PmEvidenceSnapshot $pmEvidenceSnapshot): bool
    {
        return $authUser->can('restore_pm::evidence::snapshot');
    }

    public function forceDelete(AuthUser $authUser, PmEvidenceSnapshot $pmEvidenceSnapshot): bool
    {
        return $authUser->can('force_delete_pm::evidence::snapshot');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_pm::evidence::snapshot');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_pm::evidence::snapshot');
    }

    public function replicate(AuthUser $authUser, PmEvidenceSnapshot $pmEvidenceSnapshot): bool
    {
        return $authUser->can('replicate_pm::evidence::snapshot');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_pm::evidence::snapshot');
    }

}