<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Un Gate = une question oui/non posée à l'utilisateur connecté.
        Gate::define('manage-users',  fn (User $user) => $user->role->canManageUsers());
        Gate::define('import-orders', fn (User $user) => $user->role->canImport());
        Gate::define('export-orders', fn (User $user) => $user->role->canExport());
    }
}
