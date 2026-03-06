<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;


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
        Paginator::useBootstrapFive();




        Gate::define('manage-users', function (User $user) {


            return strtolower($user->role->name) === 'superadmin';
        });

        // $this->registerPolicies();

        Gate::define('manage-academic-years', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'teacher']));
        Gate::define('manage-terms', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'teacher']));


        Gate::define('manage-classes', fn ($user) => in_array($user->role->name, ['admin', 'superadmin']));
        Gate::define('manage-sections', fn ($user) => in_array($user->role->name, ['admin', 'superadmin']));
        Gate::define('manage-subjects', fn ($user) => in_array($user->role->name, ['admin', 'superadmin']));
        Gate::define('manage-students', fn ($user) => in_array($user->role->name, ['admin', 'superadmin']));
        Gate::define('manage-teachers', fn ($user) => in_array($user->role->name, ['admin', 'superadmin']));
        Gate::define('manage-attendance', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'teacher']));


        Gate::define('manage-fees', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'accountant']));
        Gate::define('manage-invoices', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'accountant']));
        Gate::define('manage-generate-invoice', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'accountant']));
        // Gate::define('manage-fees', fn ($user) => in_array($user->role->name, ['admin', 'superadmin', 'accountant']));

        // Promotions: restrict management to superadmin only
        Gate::define('manage-promotions', fn (User $user) => strtolower($user->role->name) === 'superadmin');


    }
}
