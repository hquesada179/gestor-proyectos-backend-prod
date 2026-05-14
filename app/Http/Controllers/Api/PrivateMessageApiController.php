<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateConversation;
use App\Models\Proyecto;
use App\Notifications\PrivateMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrivateMessageApiController extends Controller
{
    /**
     * GET /api/private-messages
     * List all private conversations for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $userId = request()->user()->id;

        $conversations = PrivateConversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['project', 'userOne', 'userTwo'])
            ->withCount(['messages as unread_count' => fn ($q) =>
                $q->where('sender_id', '!=', $userId)->whereNull('read_at')
            ])
            ->orderByDesc('last_message_at')
            ->get();

        $data = $conversations->map(function ($conv) use ($userId) {
            $other   = $conv->user_one_id === $userId ? $conv->userTwo : $conv->userOne;
            $lastMsg = $conv->messages()->latest()->value('body');

            return [
                'conversation_id'   => $conv->id,
                'project_id'        => $conv->project_id,
                'project_name'      => $conv->project?->nombre ?? '',
                'other_user_id'     => $other?->id,
                'other_user_name'   => $other?->name ?? '',
                'other_user_email'  => $other?->email ?? '',
                'other_user_avatar' => $other?->profile_photo_path
                    ? asset('storage/' . $other->profile_photo_path)
                    : null,
                'last_message'      => $lastMsg,
                'last_message_at'   => $conv->last_message_at?->toIso8601String(),
                'unread_count'      => $conv->unread_count,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/private-messages/start
     * Create or retrieve a private conversation between two project members.
     * Accepts recipient_id OR user_id as the target user (Android may send either or both).
     */
    public function start(Request $request): JsonResponse
    {
        \Log::info('[PrivateChat][start] request', $request->only(['user_id', 'recipient_id', 'project_id']));

        // Accept recipient_id OR user_id — prefer recipient_id when available
        $otherId = (int) ($request->input('recipient_id') ?? $request->input('user_id'));

        if (!$otherId || !$request->filled('project_id')) {
            return response()->json([
                'message' => 'Se requiere project_id y recipient_id (o user_id).',
            ], 422);
        }

        $authId    = $request->user()->id;
        $projectId = (int) $request->project_id;

        if ($otherId === $authId) {
            return response()->json(['message' => 'No puedes iniciar un chat contigo mismo.'], 400);
        }

        $proyecto = Proyecto::find($projectId);
        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (!$proyecto->isAccessibleBy($authId)) {
            return response()->json(['message' => 'No tienes acceso a este proyecto.'], 403);
        }

        if (!$proyecto->isAccessibleBy($otherId)) {
            return response()->json(['message' => 'El usuario no pertenece a este proyecto.'], 403);
        }

        // Normalize order to avoid duplicate rows (always min < max)
        $userOneId = min($authId, $otherId);
        $userTwoId = max($authId, $otherId);

        $conversation = PrivateConversation::firstOrCreate([
            'project_id'  => $projectId,
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);

        \Log::info('[PrivateChat][start] conversation', [
            'id'      => $conversation->id,
            'created' => $conversation->wasRecentlyCreated,
        ]);

        return response()->json([
            'message' => 'Conversación lista.',
            'data'    => [
                'id'              => $conversation->id,
                'conversation_id' => $conversation->id,
            ],
        ]);
    }

    /**
     * GET /api/private-messages/{id}
     * Retrieve messages of a private conversation.
     */
    public function show(int $id): JsonResponse
    {
        $authId = request()->user()->id;

        \Log::info('[PrivateChat][show] request', ['conversation_id' => $id, 'auth_id' => $authId]);

        $conv = PrivateConversation::with(['project', 'userOne', 'userTwo'])->find($id);

        if (!$conv) {
            return response()->json(['message' => 'Conversación no encontrada.'], 404);
        }

        if (!$conv->involvesUser($authId)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        $other = $conv->user_one_id === $authId ? $conv->userTwo : $conv->userOne;

        $msgs = $conv->messages()
            ->with('sender')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->reverse()
            ->values();

        \Log::info('[PrivateChat][show] messages returned', ['count' => $msgs->count()]);

        $formattedMsgs = $msgs->map(function ($msg) use ($conv) {
            return $this->formatMessage($msg, $conv->id);
        })->values();

        return response()->json([
            'data' => [
                'id'                => $conv->id,
                'project_id'        => $conv->project_id,
                'project_name'      => $conv->project?->nombre ?? '',
                'other_user_id'     => $other?->id,
                'other_user_name'   => $other?->name ?? '',
                'other_user_avatar' => $other?->profile_photo_path
                    ? asset('storage/' . $other->profile_photo_path)
                    : null,
                'messages'          => $formattedMsgs,
            ],
        ]);
    }

    /**
     * POST /api/private-messages/{id}
     * Send a message in a private conversation.
     * Accepts body, message, mensaje, or contenido as the text field.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $authId = $request->user()->id;

        \Log::info('[PrivateChat][store] request', [
            'conversation_id' => $id,
            'fields_received' => array_keys($request->all()),
        ]);

        $conv = PrivateConversation::with(['userOne', 'userTwo'])->find($id);

        if (!$conv) {
            return response()->json(['message' => 'Conversación no encontrada.'], 404);
        }

        if (!$conv->involvesUser($authId)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        // Accept any common field name Android might send
        $body = $request->input('body')
            ?? $request->input('message')
            ?? $request->input('mensaje')
            ?? $request->input('contenido');

        if (!$body || trim($body) === '') {
            return response()->json([
                'message' => 'El mensaje no puede estar vacío. Envía el texto en el campo body, message, mensaje o contenido.',
            ], 422);
        }

        $msg = $conv->messages()->create([
            'sender_id' => $authId,
            'body'      => strip_tags(trim($body)),
        ]);

        $conv->update(['last_message_at' => now()]);

        \Log::info('[PrivateChat][store] message saved', ['message_id' => $msg->id]);

        // Notify the receiver
        $receiver = $conv->user_one_id === $authId ? $conv->userTwo : $conv->userOne;
        if ($receiver) {
            $receiver->notify(new PrivateMessageNotification(
                $request->user()->name,
                $conv->id,
                $conv->project_id,
            ));
        }

        $msg->load('sender');

        return response()->json([
            'message' => 'Mensaje enviado correctamente.',
            'data'    => $this->formatMessage($msg, $conv->id),
        ], 201);
    }

    /**
     * POST /api/private-messages/{id}/read
     * Mark all received messages in a conversation as read.
     */
    public function markRead(int $id): JsonResponse
    {
        $authId = request()->user()->id;
        $conv   = PrivateConversation::find($id);

        if (!$conv) {
            return response()->json(['message' => 'Conversación no encontrada.'], 404);
        }

        if (!$conv->involvesUser($authId)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        $conv->messages()
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Mensajes marcados como leídos.']);
    }

    /**
     * GET /api/proyectos/{id}/members/chat
     * List project members available for private chat (excluding self).
     */
    public function projectMembers(int $id): JsonResponse
    {
        $proyecto = Proyecto::with(['user', 'members.user', 'members.role'])->find($id);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $authId = request()->user()->id;

        if (!$proyecto->isAccessibleBy($authId)) {
            return response()->json(['message' => 'No tienes acceso a este proyecto.'], 403);
        }

        $members = collect();

        // Include project owner if not self
        if ($proyecto->user && $proyecto->user->id !== $authId) {
            $members->push($this->formatMember($proyecto->user, 'Dueño'));
        }

        // Include active members with accounts, excluding self and already-added owner
        foreach ($proyecto->members as $member) {
            if (!$member->user
                || $member->user->id === $authId
                || $member->status !== 'activo'
                || $members->contains('id', $member->user->id)) {
                continue;
            }

            $members->push($this->formatMember($member->user, $member->role?->name ?? 'Miembro'));
        }

        return response()->json([
            'project' => [
                'id'   => $proyecto->id,
                'name' => $proyecto->nombre,
            ],
            'members' => $members->values(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function formatMessage($msg, int $conversationId): array
    {
        $sender = $msg->sender;
        $avatar = $sender?->profile_photo_path
            ? asset('storage/' . $sender->profile_photo_path)
            : null;

        return [
            'id'              => $msg->id,
            'conversation_id' => $conversationId,
            'user_id'         => $msg->sender_id,
            'message'         => $msg->body,
            'read_at'         => $msg->read_at?->toIso8601String(),
            'created_at'      => $msg->created_at->toIso8601String(),
            'user'            => [
                'id'        => $sender?->id,
                'name'      => $sender?->name ?? 'Usuario',
                'email'     => $sender?->email ?? '',
                'avatar'    => $avatar,
                'photo_url' => $avatar,
            ],
        ];
    }

    private function formatMember($user, string $role): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => $user->profile_photo_path
                ? asset('storage/' . $user->profile_photo_path)
                : null,
            'role'   => $role,
        ];
    }
}
