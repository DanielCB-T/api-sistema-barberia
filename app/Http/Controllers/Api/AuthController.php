<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * POST /api/register
     * Crea un cliente nuevo, le envía el correo de verificación (sistema
     * nativo de Laravel) y avisa a los administradores. La verificación NO
     * bloquea el acceso: se devuelve el token para iniciar sesión de una vez;
     * el correo de verificación queda como paso opcional/informativo.
     */
    public function register(RegisterRequest $request)
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'birthdate' => $request->birthdate,
            'password' => $request->password, // se hashea solo por el cast 'hashed'
            'role' => 'client', // el registro público siempre crea clientes
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = \App\Support\ImageStorage::store($request->file('avatar'), 'avatars');
        }

        $user = User::create($data);

        // Sistema nativo de Laravel: envía la notificación de verificación
        // (enlace firmado con expiración) al correo recién registrado. Se
        // envuelve en try/catch para que un fallo de correo NO impida crear
        // la cuenta; el error queda en el log.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('No se pudo enviar el correo de verificación.', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        // Notificación interna para administradores (campanita del Navbar).
        $this->notifications->userRegistered($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada correctamente. Te enviamos un correo para verificar tu cuenta.',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * POST /api/login
     * Valida credenciales y regresa un token real.
     *
     * NOTA: la verificación de correo ya NO bloquea el inicio de sesión. El
     * correo de verificación se sigue enviando al registrarse, pero es un
     * paso opcional; se puede acceder con la cuenta aunque no esté verificada.
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
            'user' => new UserResource($user),
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
        return new UserResource($request->user()->load('branch'));
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

    /**
     * POST /api/change-password  (requiere token)
     * A diferencia de forgot/reset-password (para cuando no puedes entrar),
     * este endpoint es para cuando SÍ tienes sesión y quieres cambiarla
     * desde "Ajustes". Pide la contraseña actual por seguridad.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->forceFill([
            'password' => $request->input('password'), // se hashea por el cast 'hashed'
        ])->save();

        // Revoca todos los tokens menos el que se está usando ahora mismo,
        // para no cerrar la sesión actual justo después de cambiar la clave.
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()?->id)->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    /**
     * PUT /api/profile  (requiere token)
     * Edita los datos propios del usuario autenticado (pantalla de Ajustes).
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->avatar;
            $data['avatar'] = \App\Support\ImageStorage::store($request->file('avatar'), 'avatars');
            \App\Support\ImageStorage::delete($oldAvatar);
        }

        $user->update($data);

        return (new UserResource($user->load('branch')))
            ->additional(['message' => 'Perfil actualizado correctamente.']);
    }
}
