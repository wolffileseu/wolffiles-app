<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_reports');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('view_reports');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('create_reports');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('update_reports');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->hasRole('admin') || $user->can('delete_reports');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('delete_reports');
    }
}