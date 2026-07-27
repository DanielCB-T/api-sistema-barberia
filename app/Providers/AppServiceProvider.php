<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        // Como no hay una vista Blade de "reset password" (esto es una API pura),
        // el correo de recuperación enlaza a una pantalla del frontend (React) que
        // luego llamará a POST /api/reset-password con este token y el email.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email={$user->getEmailForPasswordReset()}";
        });
    }
}
