<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    private const MODULES = [
        'dashboard'      => 'Dashboard',
        'proyectos'      => 'Proyectos',
        'mis_tareas'     => 'Mis Tareas',
        'requerimientos' => 'Requerimientos',
        'scrum_board'    => 'Scrum Board',
        'sprints'        => 'Sprints',
        'tablero_tareas' => 'Tablero Tareas',
        'insumos'        => 'Insumos',
        'calendario'     => 'Calendario',
        'asistente_ia'   => 'Asistente IA',
        'equipo'         => 'Equipo',
        'roles'          => 'Roles y Permisos',
    ];

    private const ACTIONS = ['ver', 'crear', 'editar', 'eliminar', 'administrar'];

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'members'])->latest()->get();
        return view('roles.index', compact('roles'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'max:20'],
        ]);

        Role::create([
            'name'        => $request->name,
            'description' => $request->description,
            'color'       => $request->color ?? '#6366f1',
        ]);

        return redirect()->route('roles.index')->with('success', "Rol «{$request->name}» creado correctamente.");
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function show(Role $role): View
    {
        $role->load(['permissions', 'members.proyecto']);
        $permissions   = Permission::all()->groupBy('module');
        $rolePermIds   = $role->permissions->pluck('id')->toArray();
        $modules       = self::MODULES;
        $actions       = self::ACTIONS;

        return view('roles.show', compact('role', 'permissions', 'rolePermIds', 'modules', 'actions'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'max:20'],
        ]);

        $role->update([
            'name'        => $request->name,
            'description' => $request->description,
            'color'       => $request->color ?? $role->color,
        ]);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', 'No se pueden eliminar roles del sistema.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    // ── Update permissions ────────────────────────────────────────────────────

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $permissionIds = $request->input('permissions', []);

        // Validate all IDs are real permissions
        $valid = Permission::whereIn('id', $permissionIds)->pluck('id')->toArray();
        $role->permissions()->sync($valid);

        return back()->with('success', 'Permisos actualizados correctamente.');
    }
}
