<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WikiArticle;
use Illuminate\Auth\Access\HandlesAuthorization;

class WikiArticlePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_wiki::article');
    }

    public function view(AuthUser $authUser, WikiArticle $wikiArticle): bool
    {
        return $authUser->can('view_wiki::article');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_wiki::article');
    }

    public function update(AuthUser $authUser, WikiArticle $wikiArticle): bool
    {
        return $authUser->can('update_wiki::article');
    }

    public function delete(AuthUser $authUser, WikiArticle $wikiArticle): bool
    {
        return $authUser->can('delete_wiki::article');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_wiki::article');
    }

    public function restore(AuthUser $authUser, WikiArticle $wikiArticle): bool
    {
        return $authUser->can('restore_wiki::article');
    }

    public function forceDelete(AuthUser $authUser, WikiArticle $wikiArticle): bool
    {
        return $authUser->can('force_delete_wiki::article');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_wiki::article');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_wiki::article');
    }

    public function replicate(AuthUser $authUser, WikiArticle $wikiArticle): bool
    {
        return $authUser->can('replicate_wiki::article');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_wiki::article');
    }

}