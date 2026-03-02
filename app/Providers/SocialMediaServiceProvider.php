<?php

namespace App\Providers;

use App\Services\SocialMedia\SocialMediaService;
use Illuminate\Support\ServiceProvider;

class SocialMediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SocialMediaService::class, function () {
            return new SocialMediaService();
        });
    }

    public function boot(): void
    {
        //
    }
}
