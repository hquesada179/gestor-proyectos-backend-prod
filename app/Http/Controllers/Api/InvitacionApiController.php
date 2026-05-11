<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvitation;
use App\Notifications\InvitationRespondedNotification;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvitacionApiController extends Controller
{
    /** GET /api/invitaciones — invitations received by the authenticated user */
    public function index(): JsonResponse
    {
        $user = request()->user();

        $invitations = ProjectInvitation::where('invited_user_id', $user->id)
            ->with(['proyecto', 'invitedBy', 'role'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $invitations->map(fn ($inv) => $this->formatInvitation($inv))->values(),
        ]);
    }

    /** POST /api/invitaciones/{id}/aceptar */
    public function accept(int $id): JsonResponse
    {
        $invitation = ProjectInvitation::with(['proyecto', 'invitedBy'])->find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Invitación no encontrada.'], 404);
        }

        $user = request()->user();

        if ($invitation->invited_user_id !== $user->id) {
            return response()->json(['message' => 'No tienes permiso para aceptar esta invitación.'], 403);
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => 'Esta invitación ya fue respondida.'], 400);
        }

        DB::transaction(function () use ($invitation, $user) {
            $invitation->proyecto->members()->updateOrCreate(
                ['email' => $invitation->email],
                [
                    'user_id' => $user->id,
                    'name'    => $user->name,
                    'email'   => $invitation->email,
                    'role_id' => $invitation->role_id,
                    'status'  => 'activo',
                ]
            );

            $invitation->update([
                'status'       => 'accepted',
                'responded_at' => now(),
            ]);

            ProjectActivityLogger::log(
                $invitation->proyecto,
                'accepted_invitation',
                'invitaciones',
                "{$user->name} aceptó la invitación al proyecto"
            );

            if ($invitation->invitedBy) {
                $invitation->invitedBy->notify(
                    new InvitationRespondedNotification($invitation, 'accepted', $user)
                );
            }
        });

        return response()->json([
            'message'    => 'Invitación aceptada correctamente.',
            'project_id' => $invitation->proyecto_id,
        ]);
    }

    /** POST /api/invitaciones/{id}/rechazar */
    public function reject(int $id): JsonResponse
    {
        $invitation = ProjectInvitation::with(['proyecto', 'invitedBy'])->find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Invitación no encontrada.'], 404);
        }

        $user = request()->user();

        if ($invitation->invited_user_id !== $user->id) {
            return response()->json(['message' => 'No tienes permiso para rechazar esta invitación.'], 403);
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => 'Esta invitación ya fue respondida.'], 400);
        }

        DB::transaction(function () use ($invitation, $user) {
            $invitation->update([
                'status'       => 'rejected',
                'responded_at' => now(),
            ]);

            $invitation->proyecto->members()
                ->where('email', $invitation->email)
                ->where('status', 'invitado')
                ->whereNull('user_id')
                ->delete();

            ProjectActivityLogger::log(
                $invitation->proyecto,
                'rejected_invitation',
                'invitaciones',
                "{$user->name} rechazó la invitación al proyecto"
            );

            if ($invitation->invitedBy) {
                $invitation->invitedBy->notify(
                    new InvitationRespondedNotification($invitation, 'rejected', $user)
                );
            }
        });

        return response()->json([
            'message' => 'Invitación rechazada correctamente.',
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'   => 'pendiente',
            'accepted'  => 'aceptada',
            'rejected'  => 'rechazada',
            'cancelled' => 'cancelada',
            default     => $status,
        };
    }

    private function formatInvitation(ProjectInvitation $inv): array
    {
        return [
            'id'               => $inv->id,
            'project_id'       => $inv->proyecto_id,
            'project_name'     => $inv->proyecto?->nombre ?? 'Proyecto',
            'invited_by_name'  => $inv->invitedBy?->name ?? '',
            'invited_by_email' => $inv->invitedBy?->email ?? '',
            'status'           => $this->statusLabel($inv->status),
            'created_at'       => $inv->created_at->toIso8601String(),
        ];
    }
}
