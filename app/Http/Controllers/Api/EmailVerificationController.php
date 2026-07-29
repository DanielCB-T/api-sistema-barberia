<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Verificación de correo con el sistema nativo de Laravel, adaptado a una
 * API sin sesión (SPA en React):
 *
 *  - El enlace del correo apunta a GET /api/email/verify/{id}/{hash}, una
 *    ruta firmada (middleware `signed`) y con expiración (60 min por
 *    config). No requiere token porque el usuario hace clic desde su correo.
 *  - Tras verificar, se redirige al frontend con un parámetro para mostrar
 *    el mensaje adecuado.
 *  - El reenvío es público por correo (evita fugas de existencia de cuenta
 *    respondiendo siempre igual).
 */
class EmailVerificationController extends Controller
{
    /**
     * GET /api/email/verify/{id}/{hash}   (middleware: signed, throttle)
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::find($id);

        // Enlace inválido: usuario inexistente o hash que no corresponde.
        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return $this->redirectToFrontend('invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectToFrontend('already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectToFrontend('success');
    }

    /**
     * POST /api/email/verify/resend   { email }   (público, throttle)
     */
    public function resend(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            Log::info('Correo de verificación reenviado.', ['email' => $data['email']]);
        }

        // Respuesta genérica: no revelamos si el correo existe o no.
        return response()->json([
            'message' => 'Si el correo existe y aún no está verificado, te enviamos un nuevo enlace de verificación.',
        ]);
    }

    /**
     * Redirige al frontend a la pantalla de login con un estado que la SPA
     * usa para mostrar el mensaje correcto (?verified=success|already|invalid).
     */
    private function redirectToFrontend(string $status): \Illuminate\Http\RedirectResponse
    {
        $frontend = rtrim(config('app.frontend_url'), '/');

        return redirect()->away("{$frontend}/?verified={$status}");
    }
}
