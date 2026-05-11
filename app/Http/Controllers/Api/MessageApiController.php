<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageApiController extends Controller
{
    /** GET /api/messages — conversation summaries for each accessible project */
    public function index(): JsonResponse
    {
        $userId = request()->user()->id;

        $proyectos = Proyecto::accessibleBy($userId)
            ->with([
                'messages' => fn ($q) => $q
                    ->where('type', 'general')
                    ->whereNull('receiver_id')
                    ->with(['sender', 'user'])
                    ->latest()
                    ->limit(1),
            ])
            ->get();

        $data = $proyectos->map(function ($p) {
            $lastMsg = $p->messages->first();

            return [
                'project_id'      => $p->id,
                'project_name'    => $p->nombre,
                'last_message'    => $lastMsg?->message,
                'last_message_at' => $lastMsg?->created_at?->toIso8601String(),
                'unread_count'    => 0,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /** GET /api/proyectos/{id}/messages */
    public function projectMessages(int $id): JsonResponse
    {
        $proyecto = Proyecto::find($id);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $userId = request()->user()->id;

        if (!$proyecto->isAccessibleBy($userId)) {
            return response()->json(['message' => 'No tienes acceso a este proyecto.'], 403);
        }

        $msgs = $proyecto->messages()
            ->where('type', 'general')
            ->whereNull('receiver_id')
            ->with(['sender', 'user'])
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'project'  => [
                'id'   => $proyecto->id,
                'name' => $proyecto->nombre,
            ],
            'messages' => $msgs->map(fn ($msg) => $this->formatMessage($msg, $userId))->values(),
        ]);
    }

    /** POST /api/proyectos/{id}/messages */
    public function store(Request $request, int $id): JsonResponse
    {
        $proyecto = Proyecto::find($id);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $userId = request()->user()->id;

        if (!$proyecto->isAccessibleBy($userId)) {
            return response()->json(['message' => 'No tienes acceso a este proyecto.'], 403);
        }

        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = $proyecto->messages()->create([
            'user_id'   => $userId,
            'sender_id' => $userId,
            'type'      => 'general',
            'message'   => strip_tags(trim($request->body)),
        ]);

        $msg->load(['sender', 'user']);

        return response()->json([
            'message' => 'Mensaje enviado correctamente.',
            'data'    => $this->formatMessage($msg, $userId),
        ], 201);
    }

    private function formatMessage($msg, int $userId): array
    {
        $sender   = $msg->sender ?? $msg->user;
        $senderId = (int) ($msg->sender_id ?? $msg->user_id);

        return [
            'id'          => $msg->id,
            'project_id'  => $msg->project_id,
            'user_id'     => $senderId,
            'user_name'   => $sender?->name ?? 'Usuario',
            'user_email'  => $sender?->email ?? null,
            'user_avatar' => $sender?->profile_photo_path
                ? asset('storage/' . $sender->profile_photo_path)
                : null,
            'body'        => $msg->message,
            'created_at'  => $msg->created_at->toIso8601String(),
            'mine'        => $senderId === $userId,
        ];
    }
}
