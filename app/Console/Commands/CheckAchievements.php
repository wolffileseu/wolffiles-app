<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Console\Command;

class CheckAchievements extends Command
{
    protected $signature = 'wolffiles:achievements {--user= : Check specific user ID}';
    protected $description = 'Check and award achievements/badges to users';

    public function handle(): int
    {
        if ($userId = $this->option('user')) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::all();
        }

        $totalAwarded = 0;

        foreach ($users as $user) {
            $awarded = AchievementService::checkAll($user);
            $totalAwarded += count($awarded);

            foreach ($awarded as $badge) {
                $this->info("Awarded \"{$badge->name}\" to {$user->name}");
            }
        }

        $this->info("Done. {$totalAwarded} badges awarded to {$users->count()} users.");
        return 0;
    }
}
