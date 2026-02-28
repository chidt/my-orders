<?php

namespace App\Providers;

use App\Models\Location;
use App\Models\Site;
use App\Models\Warehouse;
use App\Policies\LocationPolicy;
use App\Policies\RolePolicy;
use App\Policies\SitePolicy;
use App\Policies\WarehousePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\DefaultLocationManager::class, function ($app) {
            return new \App\Services\DefaultLocationManager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configureGates();
    }

    protected function configurePolicies(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Site::class, SitePolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
    }

    protected function configureGates(): void
    {
        // Define gate for admin dashboard access
        Gate::define('view_admin_dashboard', function ($user) {
            return $user->hasPermissionTo('view_admin_dashboard');
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
