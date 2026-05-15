<?php

declare(strict_types=1);

namespace App\Policies\Etui;

use App\Models\Etui\EtuiProject;
use App\Models\User;

class EtuiProjectPolicy
{
    public function view(?User $user, EtuiProject $project): bool
    {
        // Public projects are world-readable; private projects only by owner
        // or an admin moderating via Filament.
        if ($project->is_public) {
            return true;
        }
        return $user !== null && ($user->id === $project->user_id || $user->hasRole('admin'));
    }

    public function update(User $user, EtuiProject $project): bool
    {
        return $user->id === $project->user_id || $user->hasRole('admin');
    }

    public function delete(User $user, EtuiProject $project): bool
    {
        return $user->id === $project->user_id || $user->hasRole('admin');
    }
}
