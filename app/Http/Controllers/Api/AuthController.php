<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/register
     * Crea un cliente nuevo y regresa un token de acceso real.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'birthdate' => $request->birthdate,
            'password' => $request->password, // se hashea solo por el cast 'hashed'
            'role' => 'client', // el registro público siempre crea clientes
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada correctamente.',
            'user' => new \App\Http\Resources\UserResource($user),]);
    }

    /**
     * POST /api/login
     * Valida credenciales y regresa un token de acceso real.
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Auth::validate([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'user' => new \App\Http\Resources\UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * GET /api/user
     * Ruta protegida de ejemplo: regresa al usuario autenticado por el token.
     */
    public function me(Request $request)
    {
        return new \App\Http\Resources\UserResource($request->user()->load('branch'));
    }

    /**
     * POST /api/logout
     * Revoca únicamente el token con el que se hizo la petición.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * POST /api/forgot-password
     * Envía un correo con un enlace/token para restablecer la contraseña.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Se envió un correo con las instrucciones para restablecer tu contraseña.',
        ]);
    }

    /**
     * POST /api/reset-password
     * Aplica el cambio de contraseña usando el token recibido por correo.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // se hashea por el cast 'hashed'
                ])->save();

                // Revoca todos los tokens previos por seguridad.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
