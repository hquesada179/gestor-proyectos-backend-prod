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
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => ['required', 'integer'],
            'user_id'    => ['required', 'integer'],
        ]);

        $authId    = request()->user()->id;
        $projectId = (int) $request->project_id;
        $otherId   = (int) $request->user_id;

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

        return response()->json([
            'message'         => 'Conversación lista.',
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * GET /api/private-messages/{id}
     * Retrieve messages of a private conversation.
     */
    public function show(int $id): JsonResponse
    {
        $authId = request()->user()->id;
        $conv   = PrivateConversation::with(['project', 'userOne', 'userTwo'])->find($id);

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

        return response()->json([
            'conversation' => [
                'id'                => $conv->id,
                'project_id'        => $conv->project_id,
                'project_name'      => $conv->project?->nombre ?? '',
                'other_user_id'     => $other?->id,
                'other_user_name'   => $other?->name ?? '',
                'other_user_avatar' => $other?->profile_photo_path
                    ? asset('storage/' . $other->profile_photo_path)
                    : null,
            ],
            'messages' => $msgs->map(fn ($msg) => $this->formatMessage($msg, $authId))->values(),
        ]);
    }

    /**
     * POST /api/private-messages/{id}
     * Send a message in a private conversation.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $authId = request()->user()->id;
        $conv   = PrivateConversation::with(['userOne', 'userTwo'])->find($id);

        if (!$conv) {
            return response()->json(['message' => 'Conversación no encontrada.'], 404);
        }

        if (!$conv->involvesUser($authId)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = $conv->messages()->create([
            'sender_id' => $authId,
            'body'      => strip_tags(trim($request->body)),
        ]);

        $conv->update(['last_message_at' => now()]);

        // Notify the receiver
        $receiver = $conv->user_one_id === $authId ? $conv->userTwo : $conv->userOne;
        if ($receiver) {
            $receiver->notify(new PrivateMessageNotification(
                request()->user()->name,
                $conv->id,
                $conv->project_id,
            ));
        }

        return response()->json([
            'message' => 'Mensaje enviado correctamente.',
            'data'    => $this->formatMessage($msg->load('sender'), $authId),
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

    private function formatMessage($msg, int $authId): array
    {
        $sender = $msg->sender;

        return [
            'id'            => $msg->id,
            'sender_id'     => $msg->sender_id,
            'sender_name'   => $sender?->name ?? 'Usuario',
            'sender_avatar' => $sender?->profile_photo_path
                ? asset('storage/' . $sender->profile_photo_path)
                : null,
            'body'          => $msg->body,
            'mine'          => $msg->sender_id === $authId,
            'read_at'       => $msg->read_at?->toIso8601String(),
            'created_at'    => $msg->created_at->toIso8601String(),
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
