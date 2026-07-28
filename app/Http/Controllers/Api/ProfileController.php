<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;

class ProfileController extends Controller
{
    /**
     * PUT /api/profile
     *
     * Edita datos personales, preferencias de notificación y, opcionalmente,
     * la contraseña (siempre hasheada por el cast 'hashed' del modelo,
     * nunca se guarda ni se regresa en texto plano).
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $data = $request->safe()->only(['name', 'phone', 'birthdate', 'notify_whatsapp']);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);

        return (new UserResource($user->fresh()))
            ->additional(['message' => 'Tus datos se actualizaron correctamente.']);
    }
}
