<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectChatController extends Controller
{
    /** GET /mensajes/proyectos — list of projects the user can chat in. */
    public function projects(): JsonResponse
    {
        $proyectos = Proyecto::accessibleBy(Auth::id())
            ->with([
                'user',
                'members' => fn ($q) => $q->where('status', 'activo')
                                          ->whereNotNull('user_id')
                                          ->with('user'),
                'messages' => fn ($q) => $q->with('sender', 'user')
                                           ->where('type', 'general')
                                           ->whereNull('receiver_id')
                                           ->latest()
                                           ->limit(1),
            ])
            ->orderBy('nombre')
            ->get();

        $data = $proyectos->map(function ($p) {
            $members = $p->displayMembers()->map(fn ($u) => [
                'id'      => $u->id,
                'name'    => $u->name,
                'photo'   => $u->profile_photo_path
                    ? asset('storage/' . $u->profile_photo_path)
                    : null,
                'initial' => mb_strtoupper(mb_substr($u->name, 0, 1)),
            ])->values();

            $lastMsg = $p->messages->first();

            return [
                'id'           => $p->id,
                'nombre'       => $p->nombre,
                'cover_image'  => $p->cover_image ? asset('storage/' . $p->cover_image) : null,
                'members'      => $members,
                'last_message' => $lastMsg ? [
                    'text'      => mb_substr($lastMsg->message, 0, 55),
                    'time'      => $lastMsg->created_at->format('H:i'),
                    'is_mine'   => (int) ($lastMsg->sender_id ?? $lastMsg->user_id) === Auth::id(),
                    'user_name' => $lastMsg->sender?->name ?? $lastMsg->user?->name ?? '',
                ] : null,
            ];
        });

        return response()->json($data);
    }

    /**
     * GET /mensajes/proyectos/{proyecto}        — initial load (last 80)
     * GET /mensajes/proyectos/{proyecto}?after=N — incremental poll
     */
    public function messages(Request $request, Proyecto $proyecto): JsonResponse
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $type = $request->query('type', 'general');
        if (!in_array($type, ['general', 'private'], true)) {
            return response()->json(['message' => 'Tipo de conversación inválido.'], 422);
        }

        $receiverId = $type === 'private' ? (int) $request->query('receiver_id') : null;
        if ($type === 'private') {
            $receiverError = $this->validatePrivateReceiver($proyecto, $receiverId);
            if ($receiverError) {
                return $receiverError;
            }
        }

        $query = $this->conversationQuery($proyecto, $type, $receiverId)
            ->with(['sender', 'user']);

        if ($request->filled('after')) {
            $msgs = $query
                ->where('id', '>', (int) $request->after)
                ->orderBy('id')
                ->get();
        } else {
            $msgs = $query
                ->orderByDesc('id')
                ->limit(80)
                ->get()
                ->reverse()
                ->values();
        }

        return response()->json($this->format($msgs));
    }

    /** POST /mensajes/proyectos/{proyecto} — send a message. */
    public function store(Request $request, Proyecto $proyecto): JsonResponse
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'in:general,private'],
            'receiver_id' => ['nullable', 'integer'],
        ]);

        $type = $request->input('type', 'general');
        $receiverId = $type === 'private' ? (int) $request->input('receiver_id') : null;

        if ($type === 'private') {
            $receiverError = $this->validatePrivateReceiver($proyecto, $receiverId);
            if ($receiverError) {
                return $receiverError;
            }
        }

        $msg = $proyecto->messages()->create([
            'user_id'     => Auth::id(),
            'sender_id'   => Auth::id(),
            'receiver_id' => $type === 'private' ? $receiverId : null,
            'type'        => $type,
            'message'     => strip_tags(trim($request->message)),
        ]);

        $msg->load(['sender', 'user']);

        return response()->json($this->format(collect([$msg]))[0], 201);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function format($messages): array
    {
        return $messages->map(function ($msg) {
            $sender = $msg->sender ?: $msg->user;
            $senderId = (int) ($msg->sender_id ?? $msg->user_id);

            return [
                'id'           => $msg->id,
                'user_id'      => $senderId,
                'sender_id'    => $senderId,
                'receiver_id'  => $msg->receiver_id,
                'type'         => $msg->type ?? 'general',
                'user_name'    => $sender?->name ?? 'Usuario',
                'user_photo'   => $sender?->profile_photo_path
                    ? asset('storage/' . $sender->profile_photo_path)
                    : null,
                'user_initial' => mb_strtoupper(mb_substr($sender?->name ?? 'U', 0, 1)),
                'message'      => $msg->message,
                'is_mine'      => $senderId === Auth::id(),
                'time'         => $msg->created_at->format('H:i'),
                'date_label'   => $msg->created_at->isToday()
                    ? 'Hoy'
                    : ($msg->created_at->isYesterday()
                        ? 'Ayer'
                        : $msg->created_at->format('d/m/Y')),
            ];
        })->values()->all();
    }

    private function conversationQuery(Proyecto $proyecto, string $type, ?int $receiverId)
    {
        if ($type === 'private') {
            $userId = Auth::id();

            return $proyecto->messages()
                ->where('type', 'private')
                ->where(function ($q) use ($userId, $receiverId) {
                    $q->where(function ($inner) use ($userId, $receiverId) {
                        $inner->where('sender_id', $userId)
                              ->where('receiver_id', $receiverId);
                    })->orWhere(function ($inner) use ($userId, $receiverId) {
                        $inner->where('sender_id', $receiverId)
                              ->where('receiver_id', $userId);
                    });
                });
        }

        return $proyecto->messages()
            ->where('type', 'general')
            ->whereNull('receiver_id');
    }

    private function validatePrivateReceiver(Proyecto $proyecto, int $receiverId): ?JsonResponse
    {
        if (!$receiverId) {
            return response()->json([
                'errors' => ['receiver_id' => ['Selecciona un integrante para el chat privado.']],
            ], 422);
        }

        if ($receiverId === Auth::id()) {
            return response()->json([
                'errors' => ['receiver_id' => ['Selecciona otro integrante del proyecto.']],
            ], 422);
        }

        if (!$this->isAcceptedProjectUser($proyecto, $receiverId)) {
            return response()->json([
                'errors' => ['receiver_id' => ['El usuario seleccionado no pertenece activamente a este proyecto.']],
            ], 403);
        }

        return null;
    }

    private function isAcceptedProjectUser(Proyecto $proyecto, int $userId): bool
    {
        if ((int) $proyecto->user_id === $userId) {
            return true;
        }

        return $proyecto->members()
            ->where('user_id', $userId)
            ->where('status', 'activo')
            ->exists();
    }
}
