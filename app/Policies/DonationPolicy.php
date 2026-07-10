<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Donation;
use Illuminate\Auth\Access\HandlesAuthorization;

class DonationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_donation');
    }

    public function view(AuthUser $authUser, Donation $donation): bool
    {
        return $authUser->can('view_donation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_donation');
    }

    public function update(AuthUser $authUser, Donation $donation): bool
    {
        return $authUser->can('update_donation');
    }

    public function delete(AuthUser $authUser, Donation $donation): bool
    {
        return $authUser->can('delete_donation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_donation');
    }

    public function restore(AuthUser $authUser, Donation $donation): bool
    {
        return $authUser->can('restore_donation');
    }

    public function forceDelete(AuthUser $authUser, Donation $donation): bool
    {
        return $authUser->can('force_delete_donation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_donation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_donation');
    }

    public function replicate(AuthUser $authUser, Donation $donation): bool
    {
        return $authUser->can('replicate_donation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_donation');
    }

}