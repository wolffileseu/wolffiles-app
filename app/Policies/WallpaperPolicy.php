<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Wallpaper;
use Illuminate\Auth\Access\HandlesAuthorization;

class WallpaperPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_wallpaper');
    }

    public function view(AuthUser $authUser, Wallpaper $wallpaper): bool
    {
        return $authUser->can('view_wallpaper');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_wallpaper');
    }

    public function update(AuthUser $authUser, Wallpaper $wallpaper): bool
    {
        return $authUser->can('update_wallpaper');
    }

    public function delete(AuthUser $authUser, Wallpaper $wallpaper): bool
    {
        return $authUser->can('delete_wallpaper');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_wallpaper');
    }

    public function restore(AuthUser $authUser, Wallpaper $wallpaper): bool
    {
        return $authUser->can('restore_wallpaper');
    }

    public function forceDelete(AuthUser $authUser, Wallpaper $wallpaper): bool
    {
        return $authUser->can('force_delete_wallpaper');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_wallpaper');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_wallpaper');
    }

    public function replicate(AuthUser $authUser, Wallpaper $wallpaper): bool
    {
        return $authUser->can('replicate_wallpaper');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_wallpaper');
    }

}