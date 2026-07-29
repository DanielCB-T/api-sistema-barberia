<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        // Personaliza el correo de VERIFICACIÓN en español. $url es el enlace
        // firmado (con expiración) que Laravel genera hacia la ruta
        // 'verification.verify'; solo definimos el contenido del mensaje.
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifica tu cuenta - Barbería')
                ->greeting('¡Hola '.($notifiable->name ?? '').'!')
                ->line('Gracias por registrarte. Da clic en el botón para confirmar tu correo y activar tu cuenta.')
                ->action('Verificar mi correo', $url)
                ->line('Este enlace expira en 60 minutos.')
                ->line('Si tú no creaste esta cuenta, puedes ignorar este mensaje.')
                ->salutation('Saludos, el equipo de la Barbería');
        });
    }
}
