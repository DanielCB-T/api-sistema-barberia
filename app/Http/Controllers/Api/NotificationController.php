<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Siempre las del usuario autenticado (la campanita del Navbar).
     * Incluye el conteo de no leídas en `meta` para pintar el badge.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $notifications = Notification::query()
            ->forUser($userId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $unread = Notification::query()->forUser($userId)->unread()->count();

        return NotificationResource::collection($notifications)
            ->additional(['meta' => ['unread' => $unread]]);
    }

    /**
     * PATCH /api/notifications/{notification}/read
     * Marca una notificación como leída.
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $this->authorizeOwner($request, $notification);

        $notification->markAsRead();

        return (new NotificationResource($notification))
            ->additional(['message' => 'Notificación marcada como leída.']);
    }

    /**
     * POST /api/notifications/read-all
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function markAllAsRead(Request $request)
    {
        Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Todas las notificaciones se marcaron como leídas.',
        ]);
    }

    /**
     * DELETE /api/notifications/{notification}
     * Elimina una notificación del usuario.
     */
    public function destroy(Request $request, Notification $notification)
    {
        $this->authorizeOwner($request, $notification);

        $notification->delete();

        return response()->json([
            'message' => 'Notificación eliminada.',
        ]);
    }

    /**
     * Una notificación solo puede ser tocada por su propio dueño.
     */
    private function authorizeOwner(Request $request, Notification $notification): void
    {
        if ($notification->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('No tienes permisos sobre esta notificación.');
        }
    }
}
