<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteUserAccount
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($user->avatar) {
                Storage::disk('s3')->delete($user->avatar);
            }
            $user->files()->update(['user_id' => null, 'uploader_name' => 'Geloeschter Nutzer']);
            if (method_exists($user, 'forumPosts'))     $user->forumPosts()->update(['user_id' => null, 'author_name' => 'Geloeschter Nutzer']);
            if (method_exists($user, 'sentMessages'))   $user->sentMessages()->delete();
            if (method_exists($user, 'receivedMessages')) $user->receivedMessages()->delete();
            if (method_exists($user, 'profileFields'))  $user->profileFields()->delete();
            $user->notifications()->delete();
            $user->delete();
        });
    }
}
