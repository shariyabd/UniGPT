<?php

namespace App\Providers;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Infrastructure\AI\AIProviderManager;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIProviderManager::class);

        $this->app->bind(AIProviderInterface::class, function ($app) {
            return $app->make(AIProviderManager::class)->provider();
        });
    }

    public function boot(): void
    {
        //
    }
}
