<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvitation;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EquipoApiController extends Controller
{
    /** GET /api/proyectos/{project}/members */
    public function members(int $project): JsonResponse
    {
        $user    = request()->user();
        $proyecto = Proyecto::find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $proyecto->isAccessibleBy($user->id)) {
            return response()->json(['message' => 'No tienes acceso a este proyecto.'], 403);
        }

        $proyecto->load(['user', 'members.user', 'members.role']);

        $data = [];

        // Owner always first — may or may not have a project_members row
        $owner          = $proyecto->user;
        $ownerInMembers = $owner
            ? $proyecto->members->firstWhere('user_id', $owner->id)
            : null;

        if ($owner) {
            $data[] = [
                'id'                => $ownerInMembers?->id ?? null,
                'user_id'           => $owner->id,
                'name'              => $owner->name,
                'nombre'            => $owner->name,
                'email'             => $owner->email,
                'correo'            => $owner->email,
                'avatar'            => $owner->profilePhotoUrl() ?: null,
                'profile_photo_url' => $owner->profilePhotoUrl() ?: null,
                'role'              => 'owner',
                'rol'               => 'owner',
                'status'            => 'activo',
                'estado'            => 'activo',
                'is_owner'          => true,
                'es_dueno'          => true,
                'joined_at'         => $proyecto->created_at->toDateTimeString(),
            ];
        }

        // Other active members (skip if already listed as owner)
        foreach ($proyecto->members as $member) {
            if ($member->user_id && $owner && $member->user_id === $owner->id) {
                continue;
            }

            $memberUser = $member->user;

            $data[] = [
                'id'                => $member->id,
                'user_id'           => $member->user_id,
                'name'              => $memberUser ? $memberUser->name : $member->name,
                'nombre'            => $memberUser ? $memberUser->name : $member->name,
                'email'             => $memberUser ? $memberUser->email : $member->email,
                'correo'            => $memberUser ? $memberUser->email : $member->email,
                'avatar'            => $memberUser ? ($memberUser->profilePhotoUrl() ?: null) : null,
                'profile_photo_url' => $memberUser ? ($memberUser->profilePhotoUrl() ?: null) : null,
                'role'              => $member->role?->name ?? 'member',
                'rol'               => $member->role?->name ?? 'member',
                'status'            => $member->status,
                'estado'            => $member->status,
                'is_owner'          => false,
                'es_dueno'          => false,
                'joined_at'         => $member->created_at->toDateTimeString(),
            ];
        }

        $pendingCount = $proyecto->invitations()->where('status', 'pending')->count();
        $activeCount  = count(array_filter($data, fn ($m) => $m['status'] === 'activo'));

        return response()->json([
            'data'    => $data,
            'summary' => [
                'members_total'       => count($data),
                'total_miembros'      => count($data),
                'active_members'      => $activeCount,
                'activos'             => $activeCount,
                'pending_invitations' => $pendingCount,
                'invitados'           => $pendingCount,
            ],
        ]);
    }

    /** GET /api/proyectos/{project}/equipo */
    public function equipo(int $project): JsonResponse
    {
        return $this->members($project);
    }

    /** GET /api/proyectos/{project}/invitaciones */
    public function invitaciones(int $project): JsonResponse
    {
        $user    = request()->user();
        $proyecto = Proyecto::find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $proyecto->isAccessibleBy($user->id)) {
            return response()->json(['message' => 'No tienes acceso a este proyecto.'], 403);
        }

        $invitations = $proyecto->invitations()
            ->with(['invitedBy', 'role', 'invitedUser'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $invitations->map(fn ($inv) => $this->formatInvitation($inv))->values(),
        ]);
    }

    /** POST /api/proyectos/{project}/invitaciones */
    public function sendInvitacion(Request $request, int $project): JsonResponse
    {
        $user    = request()->user();
        $proyecto = Proyecto::find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $proyecto->isOwnedBy($user->id)) {
            return response()->json(['message' => 'Solo el dueño del proyecto puede invitar miembros.'], 403);
        }

        $email    = $request->input('email') ?? $request->input('correo');
        $roleName = $request->input('role')  ?? $request->input('rol', 'member');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'El correo electrónico es obligatorio y debe ser válido.'], 422);
        }

        if ($proyecto->members()->where('email', $email)->where('status', 'activo')->exists()) {
            return response()->json(['message' => 'Este usuario ya es miembro activo del proyecto.'], 422);
        }

        if ($proyecto->invitations()->where('email', $email)->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'Ya existe una invitación pendiente para este correo.'], 422);
        }

        $role        = Role::where('name', $roleName)->first();
        $invitedUser = User::where('email', $email)->first();

        $invitation = ProjectInvitation::create([
            'proyecto_id'        => $project,
            'invited_by_user_id' => $user->id,
            'invited_user_id'    => $invitedUser?->id,
            'role_id'            => $role?->id,
            'email'              => $email,
            'status'             => 'pending',
            'token'              => Str::random(64),
        ]);

        $invitation->load(['invitedBy', 'role', 'invitedUser']);

        return response()->json([
            'message' => 'Invitación enviada correctamente.',
            'data'    => $this->formatInvitation($invitation),
        ], 201);
    }

    /** DELETE /api/invitaciones/{id} */
    public function cancelInvitacion(int $id): JsonResponse
    {
        $user       = request()->user();
        $invitation = ProjectInvitation::with('proyecto')->find($id);

        if (! $invitation) {
            return response()->json(['message' => 'Invitación no encontrada.'], 404);
        }

        if (! $invitation->proyecto->isOwnedBy($user->id)) {
            return response()->json(['message' => 'No tienes permiso para cancelar esta invitación.'], 403);
        }

        if (! $invitation->isPending()) {
            return response()->json(['message' => 'Esta invitación ya fue respondida y no puede cancelarse.'], 400);
        }

        $invitation->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Invitación cancelada correctamente.']);
    }

    /** PATCH /api/proyectos/{project}/members/{userId} */
    public function updateMember(Request $request, int $project, int $userId): JsonResponse
    {
        $user    = request()->user();
        $proyecto = Proyecto::find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $proyecto->isOwnedBy($user->id)) {
            return response()->json(['message' => 'Solo el dueño del proyecto puede cambiar roles.'], 403);
        }

        if ($proyecto->user_id === $userId) {
            return response()->json(['message' => 'No puedes cambiar el rol del dueño del proyecto.'], 422);
        }

        $member = $proyecto->members()->where('user_id', $userId)->first();

        if (! $member) {
            return response()->json(['message' => 'El miembro no existe en este proyecto.'], 404);
        }

        $roleName = $request->input('role') ?? $request->input('rol');

        if (! $roleName) {
            return response()->json(['message' => 'El campo role es obligatorio.'], 422);
        }

        $role = Role::where('name', $roleName)->first();

        $member->update(['role_id' => $role?->id]);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'data'    => [
                'user_id' => $userId,
                'role'    => $roleName,
                'rol'     => $roleName,
            ],
        ]);
    }

    /** DELETE /api/proyectos/{project}/members/{userId} */
    public function removeMember(int $project, int $userId): JsonResponse
    {
        $user    = request()->user();
        $proyecto = Proyecto::find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $proyecto->isOwnedBy($user->id)) {
            return response()->json(['message' => 'Solo el dueño del proyecto puede eliminar miembros.'], 403);
        }

        if ($proyecto->user_id === $userId) {
            return response()->json(['message' => 'No puedes eliminar al dueño del proyecto.'], 422);
        }

        $member = $proyecto->members()->where('user_id', $userId)->first();

        if (! $member) {
            return response()->json(['message' => 'El miembro no existe en este proyecto.'], 404);
        }

        $member->delete();

        return response()->json(['message' => 'Miembro eliminado correctamente.']);
    }

    private function formatInvitation(ProjectInvitation $inv): array
    {
        return [
            'id'               => $inv->id,
            'project_id'       => $inv->proyecto_id,
            'proyecto_id'      => $inv->proyecto_id,
            'email'            => $inv->email,
            'correo'           => $inv->email,
            'role'             => $inv->role?->name ?? 'member',
            'rol'              => $inv->role?->name ?? 'member',
            'status'           => $inv->status,
            'estado'           => $inv->status,
            'invited_by_name'  => $inv->invitedBy?->name ?? '',
            'invited_by_email' => $inv->invitedBy?->email ?? '',
            'invited_user_id'  => $inv->invited_user_id,
            'token'            => $inv->token,
            'created_at'       => $inv->created_at?->toIso8601String(),
            'expires_at'       => null,
        ];
    }
}
