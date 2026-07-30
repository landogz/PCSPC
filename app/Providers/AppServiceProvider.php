<?php

namespace App\Providers;

use App\Services\Administration\PasswordPolicyService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(function () {
            return app(PasswordPolicyService::class)->validationRule();
        });
    }
}
