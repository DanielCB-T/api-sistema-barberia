<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta la creación de notificaciones internas (la campanita del Navbar)
 * y dispara los avisos por SMS cuando aplica.
 *
 * Mantiene los controladores limpios: cada controlador solo llama a un
 * método semántico (appointmentCreated, orderPlaced, etc.) y este servicio
 * decide a quién notificar, con qué mensaje y por qué canal.
 *
 * Tipos de notificación soportados (columna `type`):
 *   nueva_cita, cita_cancelada, cita_reagendada, pedido_realizado,
 *   pago_aprobado, nuevo_barbero, nuevo_usuario.
 */
class NotificationService
{
    public function __construct(private readonly BrevoSmsService $sms)
    {
    }

    // ------------------------------------------------------------------
    // Eventos de citas
    // ------------------------------------------------------------------

    /**
     * Nueva cita creada. Avisa al cliente, al barbero (si lo hay) y a los
     * administradores; y manda la confirmación por SMS al cliente si la
     * cita se marcó con notify_sms.
     */
    public function appointmentCreated(Appointment $appointment): void
    {
        $appointment->loadMissing(['client', 'barber', 'service', 'branch']);

        $when = $this->formatDateTime($appointment);
        $service = $appointment->service?->name ?? 'servicio';

        $this->record(
            $appointment->client_id,
            'nueva_cita',
            "Tu cita de {$service} quedó registrada para el {$when}.",
            $appointment
        );

        if ($appointment->barber_id) {
            $this->record(
                $appointment->barber_id,
                'nueva_cita',
                "Nueva cita asignada: {$service} el {$when}.",
                $appointment
            );
        }

        $this->notifyAdmins(
            'nueva_cita',
            "Nueva cita de {$appointment->client?->name} ({$service}) para el {$when}.",
            $appointment
        );

        if ($appointment->notify_sms) {
            $this->sendAppointmentSms($appointment);
        }
    }

    /**
     * Envía (o reenvía) la confirmación de la cita por SMS al cliente y
     * registra el resultado como notificación de canal sms.
     *
     * @return bool true si el mensaje se entregó a la API de Brevo.
     */
    public function sendAppointmentSms(Appointment $appointment): bool
    {
        $appointment->loadMissing(['client', 'barber', 'service', 'branch']);

        $phone = $appointment->client?->phone;
        $message = $this->buildAppointmentMessage($appointment);

        $sent = $phone
            ? $this->sms->sendText($phone, $message)
            : false;

        $this->record(
            $appointment->client_id,
            'nueva_cita',
            $message,
            $appointment,
            'sms',
            $sent ? 'enviado' : 'fallido'
        );

        return $sent;
    }

    public function appointmentCancelled(Appointment $appointment): void
    {
        $appointment->loadMissing(['client', 'barber', 'service']);

        $when = $this->formatDateTime($appointment);
        $service = $appointment->service?->name ?? 'servicio';

        $this->record(
            $appointment->client_id,
            'cita_cancelada',
            "Tu cita de {$service} del {$when} fue cancelada.",
            $appointment
        );

        if ($appointment->barber_id) {
            $this->record(
                $appointment->barber_id,
                'cita_cancelada',
                "Se canceló la cita de {$service} del {$when}.",
                $appointment
            );
        }

        $this->notifyAdmins(
            'cita_cancelada',
            "Cita cancelada: {$appointment->client?->name} ({$service}) del {$when}.",
            $appointment
        );
    }

    public function appointmentRescheduled(Appointment $appointment): void
    {
        $appointment->loadMissing(['client', 'barber', 'service']);

        $when = $this->formatDateTime($appointment);
        $service = $appointment->service?->name ?? 'servicio';

        $this->record(
            $appointment->client_id,
            'cita_reagendada',
            "Tu cita de {$service} se reagendó para el {$when}.",
            $appointment
        );

        if ($appointment->barber_id) {
            $this->record(
                $appointment->barber_id,
                'cita_reagendada',
                "Cita reagendada: {$service} ahora el {$when}.",
                $appointment
            );
        }

        $this->notifyAdmins(
            'cita_reagendada',
            "Cita reagendada: {$appointment->client?->name} ({$service}) para el {$when}.",
            $appointment
        );
    }

    // ------------------------------------------------------------------
    // Eventos de tienda
    // ------------------------------------------------------------------

    public function orderPlaced(Order $order): void
    {
        $order->loadMissing('client');
        $total = number_format((float) $order->total, 2);

        $this->record(
            $order->client_id,
            'pedido_realizado',
            "Tu pedido #{$order->id} por \${$total} se registró correctamente.",
        );

        $this->notifyAdmins(
            'pedido_realizado',
            "Nuevo pedido #{$order->id} de {$order->client?->name} por \${$total}.",
        );
    }

    public function paymentApproved(Order $order): void
    {
        $order->loadMissing('client');
        $total = number_format((float) $order->total, 2);

        $this->record(
            $order->client_id,
            'pago_aprobado',
            "Recibimos tu pago de \${$total} del pedido #{$order->id}. ¡Gracias!",
        );

        $this->notifyAdmins(
            'pago_aprobado',
            "Pago aprobado del pedido #{$order->id} ({$order->client?->name}) por \${$total}.",
        );
    }

    // ------------------------------------------------------------------
    // Eventos de altas
    // ------------------------------------------------------------------

    public function barberRegistered(User $barber): void
    {
        $this->notifyAdmins(
            'nuevo_barbero',
            "Se registró un nuevo barbero: {$barber->name}.",
        );
    }

    public function userRegistered(User $user): void
    {
        $this->notifyAdmins(
            'nuevo_usuario',
            "Nuevo usuario registrado: {$user->name} ({$user->email}).",
        );
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /**
     * Inserta una notificación para un usuario concreto.
     */
    private function record(
        int $userId,
        string $type,
        string $message,
        ?Appointment $appointment = null,
        string $channel = 'app',
        string $status = 'enviado'
    ): ?Notification {
        // Blindaje: la creación de la notificación NUNCA debe romper la
        // operación principal (crear cita, barbero, pedido, etc.). Si algo
        // falla —por ejemplo, la migración de notificaciones aún no se ha
        // ejecutado y faltan las columnas `type`/`read_at`— se registra en el
        // log y se continúa con normalidad.
        try {
            return Notification::create([
                'user_id' => $userId,
                'appointment_id' => $appointment?->id,
                'channel' => $channel,
                'type' => $type,
                'message' => $message,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar la notificación (se ignora para no romper la operación).', [
                'type' => $type,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Crea la misma notificación para todos los administradores.
     */
    private function notifyAdmins(string $type, string $message, ?Appointment $appointment = null): void
    {
        $this->admins()->each(function (User $admin) use ($type, $message, $appointment) {
            $this->record($admin->id, $type, $message, $appointment);
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function admins(): Collection
    {
        return User::query()->where('role', 'admin')->get(['id']);
    }

    /**
     * Texto de la confirmación de cita para SMS, con los datos clave:
     * fecha, hora, servicio, barbero y sucursal. Se evita usar emojis para
     * no forzar la codificación UCS-2 de los SMS (70 caracteres por
     * segmento en vez de 160, lo que encarece el envío).
     */
    private function buildAppointmentMessage(Appointment $appointment): string
    {
        $name = $appointment->client?->name ?? 'Cliente';
        $date = optional($appointment->date_time)->format('d/m/Y') ?? '';
        $time = optional($appointment->date_time)->format('H:i') ?? '';
        $service = $appointment->service?->name ?? 'Servicio';
        $barber = $appointment->barber?->name ?? 'Por asignar';
        $branch = $appointment->branch?->name ?? 'Nuestra sucursal';

        return "Hola {$name}, tu cita quedo confirmada. ".
            "Fecha: {$date} {$time}. Servicio: {$service}. ".
            "Barbero: {$barber}. Sucursal: {$branch}. ".
            'Si necesitas reagendar o cancelar, contactanos.';
    }

    private function formatDateTime(Appointment $appointment): string
    {
        return optional($appointment->date_time)->format('d/m/Y H:i') ?? 'fecha por confirmar';
    }
}
