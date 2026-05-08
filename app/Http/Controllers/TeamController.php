<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamController extends Controller
{
    // ── Project selector ──────────────────────────────────────────────────────

    public function index(): View
    {
        $proyectos = Proyecto::accessibleBy(Auth::id())
            ->withCount('members')
            ->latest()
            ->get();

        return view('team.index', compact('proyectos'));
    }

    // ── Members list for a project ───────────────────────────────────────────

    public function show(Proyecto $proyecto, Request $request): View
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $isOwner = $proyecto->isOwnedBy(Auth::id());
        $roles   = Role::orderBy('name')->get();

        $members = $proyecto->members()
            ->with('role')
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when($request->role_id, fn ($q) => $q->where('role_id', $request->role_id))
            ->when($request->status,  fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        // Load pending invitations for this project (visible to owner)
        $pendingInvitations = $isOwner
            ? $proyecto->invitations()
                ->with(['invitedUser', 'role'])
                ->where('status', 'pending')
                ->latest()
                ->get()
            : collect();

        $stats = [
            'total'     => $proyecto->members()->count(),
            'activos'   => $proyecto->members()->where('status', 'activo')->count(),
            'invitados' => $proyecto->members()->where('status', 'invitado')->count(),
            'remotos'   => $proyecto->members()->where('work_mode', 'remoto')->count(),
        ];

        return view('team.show', compact('proyecto', 'members', 'roles', 'stats', 'isOwner', 'pendingInvitations'));
    }

    // ── Add member (smart: invite if account exists) ──────────────────────────

    public function storeMember(Request $request, Proyecto $proyecto): RedirectResponse
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $request->validate([
            'name'      => ['nullable', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'position'  => ['nullable', 'string', 'max:100'],
            'work_mode' => ['nullable', 'in:presencial,remoto,hibrido'],
            'location'  => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $email = strtolower(trim($request->email));

        // Guard: cannot invite yourself
        if ($email === strtolower(Auth::user()->email)) {
            return back()->withErrors(['email' => 'No puedes invitarte a ti mismo.'])->withInput();
        }

        // Guard: already an active member
        if ($proyecto->members()->where('email', $email)->where('status', 'activo')->exists()) {
            return back()->withErrors(['email' => 'Este correo ya es miembro activo del proyecto.'])->withInput();
        }

        // Check if the email belongs to a registered user
        $invitedUser = User::where('email', $email)->first();

        if ($invitedUser) {
            // Guard: avoid duplicate pending invitations
            $existingInvitation = ProjectInvitation::where('proyecto_id', $proyecto->id)
                ->where('email', $email)
                ->where('status', 'pending')
                ->first();

            if ($existingInvitation) {
                return back()->withErrors(['email' => 'Ya existe una invitación pendiente para este correo.'])->withInput();
            }

            // Create invitation record
            ProjectInvitation::create([
                'proyecto_id'        => $proyecto->id,
                'invited_by_user_id' => Auth::id(),
                'invited_user_id'    => $invitedUser->id,
                'role_id'            => $request->role_id,
                'email'              => $email,
                'status'             => 'pending',
            ]);

            // Create/update placeholder member for display in team list
            $proyecto->members()->updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $invitedUser->name,
                    'email'     => $email,
                    'role_id'   => $request->role_id,
                    'position'  => $request->position,
                    'work_mode' => $request->work_mode,
                    'location'  => $request->location,
                    'notes'     => $request->notes,
                    'status'    => 'invitado',
                    'user_id'   => null,
                ]
            );

            return back()->with('success', "Invitación enviada a {$email}. Estará pendiente hasta que el usuario acepte.");
        }

        // External member: no account in the system
        if (empty($request->name)) {
            return back()->withErrors(['name' => 'El nombre es requerido para miembros externos.'])->withInput();
        }

        // Guard: avoid duplicate external members
        if ($proyecto->members()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'Ya existe un miembro con ese correo en este proyecto.'])->withInput();
        }

        $proyecto->members()->create([
            'name'      => $request->name,
            'email'     => $email,
            'role_id'   => $request->role_id,
            'position'  => $request->position,
            'work_mode' => $request->work_mode,
            'location'  => $request->location,
            'notes'     => $request->notes,
            'status'    => 'invitado',
            'user_id'   => null,
        ]);

        return back()->with('success', 'Miembro externo agregado. No tiene cuenta en el sistema todavía.');
    }

    // ── Edit member ───────────────────────────────────────────────────────────

    public function updateMember(Request $request, ProjectMember $member): RedirectResponse
    {
        abort_if(!$member->proyecto->isOwnedBy(Auth::id()), 403);

        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'position'  => ['nullable', 'string', 'max:100'],
            'status'    => ['required', 'in:activo,inactivo,invitado,suspendido'],
            'work_mode' => ['nullable', 'in:presencial,remoto,hibrido'],
            'location'  => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $member->update($request->only(['name', 'email', 'role_id', 'position', 'status', 'work_mode', 'location', 'notes']));

        return back()->with('success', 'Miembro actualizado correctamente.');
    }

    // ── Delete member ─────────────────────────────────────────────────────────

    public function destroyMember(ProjectMember $member): JsonResponse|RedirectResponse
    {
        abort_if(!$member->proyecto->isOwnedBy(Auth::id()), 403);

        // Cancel any pending invitation for this email
        ProjectInvitation::where('proyecto_id', $member->proyecto_id)
            ->where('email', $member->email)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $member->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Miembro eliminado del equipo.');
    }
}
