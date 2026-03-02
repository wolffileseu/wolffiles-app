<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $properties = null,
        ?int $userId = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }

    // Auth Events
    public static function login($user): void
    {
        static::log('login', 'App\Models\User', $user->id, [
            'method' => session('login_method', 'email'),
            'user_agent' => request()->userAgent(),
        ], $user->id);
    }

    public static function logout($user): void
    {
        static::log('logout', 'App\Models\User', $user->id, null, $user->id);
    }

    public static function register($user): void
    {
        static::log('register', 'App\Models\User', $user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'method' => $user->discord_id ? 'discord' : 'email',
        ], $user->id);
    }

    public static function loginFailed(string $email): void
    {
        static::log('login_failed', null, null, [
            'email' => $email,
            'user_agent' => request()->userAgent(),
        ]);
    }

    // File Events
    public static function fileUpload($file, $user = null): void
    {
        static::log('upload', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'category' => $file->category?->name,
            'file_size' => $file->file_size_formatted ?? null,
        ], $user?->id);
    }

    public static function fileDownload($file): void
    {
        static::log('download', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'file_size' => $file->file_size_formatted ?? null,
        ]);
    }

    public static function fileApprove($file): void
    {
        static::log('approve', 'App\Models\File', $file->id, [
            'title' => $file->title,
        ]);
    }

    public static function fileReject($file, ?string $reason = null): void
    {
        static::log('reject', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'reason' => $reason,
        ]);
    }

    public static function fileDelete($file): void
    {
        static::log('delete', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'category' => $file->category?->name,
        ]);
    }

    public static function fileEdit($file, array $changes = []): void
    {
        static::log('edit', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'changes' => $changes,
        ]);
    }

    // Comment Events
    public static function comment($comment, $file): void
    {
        static::log('comment', 'App\Models\Comment', $comment->id, [
            'file_title' => $file->title,
            'file_id' => $file->id,
            'excerpt' => \Illuminate\Support\Str::limit($comment->body, 100),
        ]);
    }

    public static function commentDelete($comment): void
    {
        static::log('comment_delete', 'App\Models\Comment', $comment->id, [
            'excerpt' => \Illuminate\Support\Str::limit($comment->body ?? '', 100),
        ]);
    }

    // Rating Events
    public static function rate($file, float $rating): void
    {
        static::log('rate', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'rating' => $rating,
        ]);
    }

    // Favorite Events
    public static function favorite($file): void
    {
        static::log('favorite', 'App\Models\File', $file->id, [
            'title' => $file->title,
        ]);
    }

    public static function unfavorite($file): void
    {
        static::log('unfavorite', 'App\Models\File', $file->id, [
            'title' => $file->title,
        ]);
    }

    // Report Events
    public static function report($file, string $reason): void
    {
        static::log('report', 'App\Models\File', $file->id, [
            'title' => $file->title,
            'reason' => $reason,
        ]);
    }

    // Search Events
    public static function search(string $query, int $results = 0): void
    {
        static::log('search', null, null, [
            'query' => $query,
            'results' => $results,
        ]);
    }

    // Admin Events
    public static function adminAction(string $action, string $modelType, $modelId, array $extra = []): void
    {
        static::log('admin_' . $action, $modelType, $modelId, $extra);
    }

    // Wiki & Tutorial Events
    public static function wikiSubmit($article): void
    {
        static::log('wiki_submit', 'App\Models\WikiArticle', $article->id, [
            'title' => $article->title,
        ]);
    }

    public static function tutorialSubmit($tutorial): void
    {
        static::log('tutorial_submit', 'App\Models\Tutorial', $tutorial->id, [
            'title' => $tutorial->title,
        ]);
    }

    // Donation Events
    public static function donation($donation): void
    {
        static::log('donation', 'App\Models\Donation', $donation->id, [
            'amount' => $donation->amount,
            'currency' => $donation->currency ?? 'EUR',
            'donor' => $donation->donor_name ?? 'Anonymous',
        ]);
    }

    // Poll Events
    public static function pollVote($poll, $optionId): void
    {
        static::log('poll_vote', 'App\Models\Poll', $poll->id, [
            'poll_title' => $poll->question ?? $poll->title ?? null,
            'option_id' => $optionId,
        ]);
    }

    // Contact Events
    public static function contactSubmit(string $category, string $subject): void
    {
        static::log('contact', null, null, [
            'category' => $category,
            'subject' => $subject,
        ]);
    }

    // Profile Events
    public static function profileUpdate($user, array $changes = []): void
    {
        static::log('profile_update', 'App\Models\User', $user->id, [
            'changes' => $changes,
        ], $user->id);
    }

    public static function settingsChange(string $setting, $oldValue, $newValue): void
    {
        static::log('settings_change', null, null, [
            'setting' => $setting,
            'old' => $oldValue,
            'new' => $newValue,
        ]);
    }

    // FastDL Events
    public static function fastdlDownload(string $filename, string $game): void
    {
        static::log('fastdl_download', null, null, [
            'filename' => $filename,
            'game' => $game,
        ]);
    }

    public static function fastdlUpload($file, $clan): void
    {
        static::log('fastdl_upload', null, null, [
            'filename' => $file,
            'clan' => $clan,
        ]);
    }
}
