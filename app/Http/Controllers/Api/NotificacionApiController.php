<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NotificacionApiController extends Controller
{
    /** GET /api/notificaciones */
    public function index(): JsonResponse
    {
        $user   = request()->user();
        $notifs = $user->notifications()->latest()->limit(50)->get();

        $data = $notifs->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type'] ?? 'general',
            'title'      => $n->data['title'] ?? 'Notificación',
            'body'       => $n->data['body'] ?? '',
            'read'       => !is_null($n->read_at),
            'created_at' => $n->created_at->toIso8601String(),
        ])->values();

        return response()->json([
            'data'         => $data,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /** POST /api/notificaciones/{id}/leer */
    public function markRead(string $id): JsonResponse
    {
        $user  = request()->user();
        $notif = $user->notifications()->where('id', $id)->first();

        if (!$notif) {
            return response()->json(['message' => 'Notificación no encontrada.'], 404);
        }

        $notif->markAsRead();

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    /** POST /api/notificaciones/leer-todas */
    public function markAllRead(): JsonResponse
    {
        request()->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas.']);
    }
}
