<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LuaScript;
use Illuminate\Auth\Access\HandlesAuthorization;

class LuaScriptPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_lua::script');
    }

    public function view(AuthUser $authUser, LuaScript $luaScript): bool
    {
        return $authUser->can('view_lua::script');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_lua::script');
    }

    public function update(AuthUser $authUser, LuaScript $luaScript): bool
    {
        return $authUser->can('update_lua::script');
    }

    public function delete(AuthUser $authUser, LuaScript $luaScript): bool
    {
        return $authUser->can('delete_lua::script');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_lua::script');
    }

    public function restore(AuthUser $authUser, LuaScript $luaScript): bool
    {
        return $authUser->can('restore_lua::script');
    }

    public function forceDelete(AuthUser $authUser, LuaScript $luaScript): bool
    {
        return $authUser->can('force_delete_lua::script');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_lua::script');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_lua::script');
    }

    public function replicate(AuthUser $authUser, LuaScript $luaScript): bool
    {
        return $authUser->can('replicate_lua::script');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_lua::script');
    }

}