<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolApiController extends Controller
{
    /** GET /api/roles */
    public function index(): JsonResponse
    {
        $roles = Role::withCount(['permissions', 'members'])->latest()->get();

        return response()->json([
            'data' => $roles->map(fn ($r) => $this->formatRoleSummary($r))->values(),
        ]);
    }

    /** GET /api/roles/{role} */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'members.user']);

        return response()->json([
            'data' => $this->formatRoleDetail($role),
        ]);
    }

    /** POST /api/roles */
    public function store(Request $request): JsonResponse
    {
        $name        = $request->input('name')        ?? $request->input('nombre');
        $description = $request->input('description') ?? $request->input('descripcion');
        $color       = $request->input('color', '#6366f1');
        $permissionIds = $request->input('permissions') ?? $request->input('permisos', []);

        if (! $name) {
            return response()->json(['message' => 'El campo name es obligatorio.'], 422);
        }

        if (Role::where('name', $name)->exists()) {
            return response()->json(['message' => 'Ya existe un rol con ese nombre.'], 422);
        }

        $role = Role::create([
            'name'        => $name,
            'description' => $description,
            'color'       => $color,
            'is_system'   => false,
        ]);

        if (! empty($permissionIds)) {
            $valid = Permission::whereIn('id', $permissionIds)->pluck('id')->toArray();
            $role->permissions()->sync($valid);
        }

        $role->load('permissions');
        $role->loadCount(['permissions', 'members']);

        return response()->json([
            'message' => "Rol «{$role->name}» creado correctamente.",
            'data'    => $this->formatRoleDetail($role),
        ], 201);
    }

    /** PATCH /api/roles/{role} */
    public function update(Request $request, Role $role): JsonResponse
    {
        $name        = $request->input('name')        ?? $request->input('nombre',        $role->name);
        $description = $request->input('description') ?? $request->input('descripcion',   $role->description);
        $color       = $request->input('color',       $role->color);
        $permissionIds = $request->input('permissions') ?? $request->input('permisos');

        if (Role::where('name', $name)->where('id', '!=', $role->id)->exists()) {
            return response()->json(['message' => 'Ya existe otro rol con ese nombre.'], 422);
        }

        $role->update([
            'name'        => $name,
            'description' => $description,
            'color'       => $color,
        ]);

        if ($permissionIds !== null) {
            $valid = Permission::whereIn('id', (array) $permissionIds)->pluck('id')->toArray();
            $role->permissions()->sync($valid);
        }

        $role->load('permissions');
        $role->loadCount(['permissions', 'members']);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'data'    => $this->formatRoleDetail($role),
        ]);
    }

    /** DELETE /api/roles/{role} */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['message' => 'No se pueden eliminar roles del sistema.'], 403);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }

    /** PATCH /api/roles/{role}/permisos */
    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        $permissionIds = $request->input('permissions') ?? $request->input('permisos', []);

        $valid = Permission::whereIn('id', (array) $permissionIds)->pluck('id')->toArray();
        $role->permissions()->sync($valid);

        $role->load('permissions');
        $role->loadCount('permissions');

        return response()->json([
            'message' => 'Permisos actualizados correctamente.',
            'data'    => [
                'role_id'          => $role->id,
                'permissions'      => $role->permissions->map(fn ($p) => $this->formatPermission($p))->values(),
                'permissions_count' => $role->permissions_count,
            ],
        ]);
    }

    /** GET /api/permisos  y  GET /api/permissions */
    public function permisos(): JsonResponse
    {
        $permissions = Permission::orderBy('module')->orderBy('action')->get();

        return response()->json([
            'data' => $permissions->map(fn ($p) => $this->formatPermission($p))->values(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatRoleSummary(Role $role): array
    {
        return [
            'id'               => $role->id,
            'name'             => $role->name,
            'nombre'           => $role->name,
            'description'      => $role->description,
            'descripcion'      => $role->description,
            'color'            => $role->color,
            'scope'            => $role->is_system ? 'sistema' : 'custom',
            'tipo'             => $role->is_system ? 'sistema' : 'custom',
            'is_system'        => $role->is_system,
            'permissions_count' => $role->permissions_count ?? 0,
            'permisos_count'   => $role->permissions_count ?? 0,
            'members_count'    => $role->members_count ?? 0,
            'miembros_count'   => $role->members_count ?? 0,
            'permissions'      => [],
        ];
    }

    private function formatRoleDetail(Role $role): array
    {
        $permissions = $role->relationLoaded('permissions')
            ? $role->permissions->map(fn ($p) => $this->formatPermission($p))->values()
            : [];

        $members = [];
        if ($role->relationLoaded('members')) {
            foreach ($role->members as $member) {
                $members[] = [
                    'id'      => $member->id,
                    'user_id' => $member->user_id,
                    'name'    => $member->user?->name ?? $member->name,
                    'email'   => $member->user?->email ?? $member->email,
                    'status'  => $member->status,
                ];
            }
        }

        return [
            'id'               => $role->id,
            'name'             => $role->name,
            'nombre'           => $role->name,
            'description'      => $role->description,
            'descripcion'      => $role->description,
            'color'            => $role->color,
            'scope'            => $role->is_system ? 'sistema' : 'custom',
            'tipo'             => $role->is_system ? 'sistema' : 'custom',
            'is_system'        => $role->is_system,
            'permissions_count' => isset($role->permissions_count) ? $role->permissions_count : count($permissions),
            'permisos_count'   => isset($role->permissions_count) ? $role->permissions_count : count($permissions),
            'members_count'    => isset($role->members_count) ? $role->members_count : count($members),
            'miembros_count'   => isset($role->members_count) ? $role->members_count : count($members),
            'permissions'      => $permissions,
            'permisos'         => $permissions,
            'members'          => $members,
            'miembros'         => $members,
        ];
    }

    private function formatPermission(Permission $permission): array
    {
        return [
            'id'     => $permission->id,
            'name'   => $permission->module . '.' . $permission->action,
            'module' => $permission->module,
            'action' => $permission->action,
            'label'  => $permission->label,
        ];
    }
}
