<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\Service;
use App\Services\AppointmentStatusMachine;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AppointmentController extends Controller
{
    private const RELATIONS = ['client', 'barber', 'service', 'branch', 'statusHistory'];

    /**
     * GET /api/appointments
     *
     * - Cliente: solo ve SUS citas.
     * - Barbero: solo ve las citas asignadas a él.
     * - Admin: ve todas, con filtros.
     *
     * Filtros (query params, se resuelven del lado del servidor):
     * ?status=confirmada&branch_id=1&date_from=2026-07-01&date_to=2026-07-31&per_page=10
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 10);

        $query = Appointment::query()->with(self::RELATIONS);

        if ($user->isClient()) {
            $query->where('client_id', $user->id);
        } elseif ($user->isBarber()) {
            $query->where('barber_id', $user->id);
        }
        // admin: sin restricción adicional

        $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_time', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date_time', '<=', $request->input('date_to')))
            ->orderBy('date_time', 'desc');

        return AppointmentResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/appointments  (cliente autenticado agenda para sí mismo)
     */
    public function store(StoreAppointmentRequest $request)
    {
        $user = $request->user();
        $service = Service::findOrFail($request->service_id);

        $appointment = Appointment::create([
            'client_id' => $user->id,
            'barber_id' => $request->barber_id,
            'service_id' => $service->id,
            'branch_id' => $request->branch_id,
            'date_time' => $request->date_time,
            'duration' => $service->duration,
            'status' => 'pendiente',
            'pay_online' => $request->boolean('pay_online'),
            'notify_whatsapp' => $request->boolean('notify_whatsapp', true),
        ]);

        AppointmentStatusHistory::create([
            'appointment_id' => $appointment->id,
            'status' => 'pendiente',
            'changed_by' => $user->id,
            'note' => 'Cita creada por el cliente.',
        ]);

        return (new AppointmentResource($appointment->load(self::RELATIONS)))
            ->additional(['message' => 'Cita agendada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/appointments/{appointment}
     */
    public function show(Request $request, Appointment $appointment)
    {
        $this->authorizeAccess($request, $appointment);

        return new AppointmentResource($appointment->load(self::RELATIONS));
    }

    /**
     * PUT/PATCH /api/appointments/{appointment}
     * Reprogramación: cambiar fecha/hora, servicio o barbero.
     * Solo se permite si la cita sigue "pendiente" o "confirmada".
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorizeAccess($request, $appointment);

        if (! in_array($appointment->status, ['pendiente', 'confirmada'], true)) {
            throw ValidationException::withMessages([
                'status' => ["No se puede reprogramar una cita en estado \"{$appointment->status}\"."],
            ]);
        }

        $data = $request->validated();

        if (isset($data['service_id'])) {
            $data['duration'] = Service::findOrFail($data['service_id'])->duration;
        }

        $appointment->update($data);

        AppointmentStatusHistory::create([
            'appointment_id' => $appointment->id,
            'status' => $appointment->status,
            'changed_by' => $request->user()->id,
            'note' => 'Se solicitó reagendación (cambio de fecha/servicio/barbero).',
        ]);

        return (new AppointmentResource($appointment->load(self::RELATIONS)))
            ->additional(['message' => 'Cita reprogramada correctamente.']);
    }

    /**
     * PATCH /api/appointments/{appointment}/status
     * Único punto de entrada para aceptar, confirmar, posponer, cancelar o
     * completar una cita. Aplica la máquina de estados: una transición no
     * permitida devuelve 422, nunca se aplica "a la fuerza".
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment)
    {
        $user = $request->user();
        $this->authorizeAccess($request, $appointment);

        $newStatus = $request->input('status');

        // Un cliente solo puede cancelar su propia cita, no confirmarla/completarla.
        if ($user->isClient() && $newStatus !== 'cancelada') {
            throw new AccessDeniedHttpException('Un cliente solo puede cancelar su cita, no cambiarla a otro estado.');
        }

        if (! AppointmentStatusMachine::canTransition($appointment->status, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => [
                    "No se puede pasar de \"{$appointment->status}\" a \"{$newStatus}\". ".
                    'Transiciones permitidas: '.
                    (implode(', ', AppointmentStatusMachine::allowedFrom($appointment->status)) ?: 'ninguna (estado final)'),
                ],
            ]);
        }

        $appointment->update(['status' => $newStatus]);

        AppointmentStatusHistory::create([
            'appointment_id' => $appointment->id,
            'status' => $newStatus,
            'changed_by' => $user->id,
            'note' => $request->input('note'),
        ]);

        return (new AppointmentResource($appointment->load(self::RELATIONS)))
            ->additional(['message' => "Cita actualizada a estado \"{$newStatus}\"."]);
    }

    /**
     * DELETE /api/appointments/{appointment}  (solo admin)
     * Borrado definitivo (no es un cambio de estado); usado desde el panel
     * de administración para eliminar citas de prueba o erróneas.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json([
            'message' => 'Cita eliminada correctamente.',
        ]);
    }

    /**
     * Un cliente solo puede tocar sus propias citas; un barbero solo las
     * asignadas a él; el admin puede con todas.
     */
    private function authorizeAccess(Request $request, Appointment $appointment): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isClient() && $appointment->client_id === $user->id) {
            return;
        }

        if ($user->isBarber() && $appointment->barber_id === $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('No tienes permisos sobre esta cita.');
    }
}
