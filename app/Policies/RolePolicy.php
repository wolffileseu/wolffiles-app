<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_roles');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('view_roles');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('create_roles');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('update_roles');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('delete_roles');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('delete_roles');
    }
}