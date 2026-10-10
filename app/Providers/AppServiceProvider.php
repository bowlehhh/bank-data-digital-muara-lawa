<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('view-dashboard', fn (User $user): bool => $user->is_active);
        Gate::define('upload-documents', fn (User $user): bool => $user->is_active);
    }
}
