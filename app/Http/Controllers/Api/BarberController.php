<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Barber\StoreBarberRequest;
use App\Http\Requests\Barber\UpdateBarberRequest;
use App\Http\Resources\BarberResource;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Http\Request;

/**
 * Un "barbero" es un User con role = barber. No existe una tabla propia,
 * así que aquí no se usa route-model-binding implícito de Laravel: se
 * busca manualmente con where('role', 'barber') para no confundirlo con
 * un cliente o un admin que comparta el mismo id.
 */
class BarberController extends Controller
{
    /**
     * GET /api/barbers (público)
     */
    public function index(Request $request)
    {
        $barbers = User::query()
            ->where('role', 'barber')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->with('branch')
            ->orderBy('name')
            ->get();

        return BarberResource::collection($barbers);
    }

    /**
     * POST /api/barbers (solo admin)
     */
    public function store(StoreBarberRequest $request)
    {
        $data = $request->validated();
        $data['role'] = 'barber';

        if ($request->hasFile('avatar')) {
            $data['avatar'] = ImageStorage::store($request->file('avatar'), 'avatars');
        }

        $barber = User::create($data);

        return (new BarberResource($barber->load('branch')))
            ->additional(['message' => 'Barbero creado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/barbers/{barber} (solo admin)
     */
    public function update(UpdateBarberRequest $request, string $barber)
    {
        $barberModel = User::where('role', 'barber')->findOrFail($barber);

        $data = $request->validated();

        // La contraseña es opcional: si viene vacía o no se envía, no se toca.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $oldAvatar = $barberModel->avatar;
            $data['avatar'] = ImageStorage::store($request->file('avatar'), 'avatars');
            ImageStorage::delete($oldAvatar);
        }

        $barberModel->update($data);

        return (new BarberResource($barberModel->load('branch')))
            ->additional(['message' => 'Barbero actualizado correctamente.']);
    }

    /**
     * DELETE /api/barbers/{barber} (solo admin)
     * Las citas ya agendadas con este barbero NO se borran (barber_id queda
     * en null gracias al nullOnDelete de la migración de appointments).
     */
    public function destroy(string $barber)
    {
        $barberModel = User::where('role', 'barber')->findOrFail($barber);

        ImageStorage::delete($barberModel->avatar);
        $barberModel->delete();

        return response()->json([
            'message' => 'Barbero eliminado correctamente.',
        ]);
    }
}
