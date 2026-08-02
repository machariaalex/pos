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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Theft-prevention gates: attendants sell and view stock/selling prices only,
        // unless the Owner has explicitly granted them more via User::PERMISSIONS.
        Gate::define('view-buying-price', fn (User $user) => $user->canApprove() || $user->hasPermission('view-buying-price'));
        Gate::define('view-profit', fn (User $user) => $user->canSeeProfit() || $user->hasPermission('view-profit'));
        Gate::define('edit-price', fn (User $user) => $user->canApprove() || $user->hasPermission('edit-price'));
        Gate::define('apply-discount', fn (User $user) => $user->canApprove() || $user->hasPermission('apply-discount'));
        Gate::define('adjust-stock', fn (User $user) => $user->canApprove() || $user->hasPermission('adjust-stock'));
        Gate::define('void-sale', fn (User $user) => $user->canApprove());
        Gate::define('refund-sale', fn (User $user) => $user->canApprove());
        Gate::define('manage-users', fn (User $user) => $user->isOwner() || $user->hasPermission('manage-users'));
        Gate::define('override-credit-limit', fn (User $user) => $user->isOwner());
        Gate::define('manage-credit-limit', fn (User $user) => $user->canApprove() || $user->hasPermission('manage-credit-limit'));
        Gate::define('view-audit-log', fn (User $user) => $user->isOwner() || $user->hasPermission('view-audit-log'));
        Gate::define('view-reports', fn (User $user) => $user->canApprove() || $user->hasPermission('view-reports'));
    }
}
