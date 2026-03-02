<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WikiArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_wiki_articles');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('view_wiki_articles');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('create_wiki_articles');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('update_wiki_articles');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('delete_wiki_articles');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('delete_wiki_articles');
    }
}