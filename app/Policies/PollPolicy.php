<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_polls');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('view_polls');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('create_polls');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('update_polls');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('delete_polls');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('delete_polls');
    }
}