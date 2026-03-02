<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_files');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('view_files');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('create_files');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('update_files');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('delete_files');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('delete_files');
    }
}