<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WikiCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class WikiCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_wiki::category');
    }

    public function view(AuthUser $authUser, WikiCategory $wikiCategory): bool
    {
        return $authUser->can('view_wiki::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_wiki::category');
    }

    public function update(AuthUser $authUser, WikiCategory $wikiCategory): bool
    {
        return $authUser->can('update_wiki::category');
    }

    public function delete(AuthUser $authUser, WikiCategory $wikiCategory): bool
    {
        return $authUser->can('delete_wiki::category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_wiki::category');
    }

    public function restore(AuthUser $authUser, WikiCategory $wikiCategory): bool
    {
        return $authUser->can('restore_wiki::category');
    }

    public function forceDelete(AuthUser $authUser, WikiCategory $wikiCategory): bool
    {
        return $authUser->can('force_delete_wiki::category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_wiki::category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_wiki::category');
    }

    public function replicate(AuthUser $authUser, WikiCategory $wikiCategory): bool
    {
        return $authUser->can('replicate_wiki::category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_wiki::category');
    }

}