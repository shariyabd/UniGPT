<?php

namespace App\Domain\User\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Infrastructure\Repositories\EloquentUserRepository;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
