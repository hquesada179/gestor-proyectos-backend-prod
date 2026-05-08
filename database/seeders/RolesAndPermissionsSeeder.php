<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    private const MODULES = [
        'dashboard'       => 'Dashboard',
        'proyectos'       => 'Proyectos',
        'mis_tareas'      => 'Mis Tareas',
        'requerimientos'  => 'Requerimientos',
        'scrum_board'     => 'Scrum Board',
        'sprints'         => 'Sprints',
        'tablero_tareas'  => 'Tablero Tareas',
        'insumos'         => 'Insumos',
        'calendario'      => 'Calendario',
        'asistente_ia'    => 'Asistente IA',
        'equipo'          => 'Equipo',
        'roles'           => 'Roles y Permisos',
    ];

    private const ACTIONS = [
        'ver'         => 'Ver',
        'crear'       => 'Crear',
        'editar'      => 'Editar',
        'eliminar'    => 'Eliminar',
        'administrar' => 'Administrar',
    ];

    private const INITIAL_ROLES = [
        ['name' => 'Administrador',      'description' => 'Acceso completo a todos los módulos y configuraciones.', 'color' => '#6366f1', 'is_system' => true],
        ['name' => 'Líder de Proyecto',  'description' => 'Lidera el proyecto, gestiona equipo y asigna tareas.', 'color' => '#3b82f6', 'is_system' => true],
        ['name' => 'Scrum Master',       'description' => 'Facilita el proceso Scrum y gestiona sprints.', 'color' => '#8b5cf6', 'is_system' => true],
        ['name' => 'Desarrollador',      'description' => 'Acceso a tareas, sprints y scrum board.', 'color' => '#10b981', 'is_system' => true],
        ['name' => 'Diseñador',          'description' => 'Acceso a tareas y requerimientos de diseño.', 'color' => '#f59e0b', 'is_system' => true],
        ['name' => 'QA',                 'description' => 'Control de calidad y revisión de tareas.', 'color' => '#ef4444', 'is_system' => true],
        ['name' => 'Cliente',            'description' => 'Acceso de solo lectura a avances del proyecto.', 'color' => '#64748b', 'is_system' => false],
        ['name' => 'Auditor',            'description' => 'Revisión y auditoría sin modificar datos.', 'color' => '#94a3b8', 'is_system' => false],
    ];

    public function run(): void
    {
        // Create permissions
        $allPermissions = [];
        foreach (self::MODULES as $moduleKey => $moduleLabel) {
            foreach (self::ACTIONS as $actionKey => $actionLabel) {
                $perm = Permission::firstOrCreate(
                    ['module' => $moduleKey, 'action' => $actionKey],
                    ['label'  => "{$actionLabel} {$moduleLabel}"]
                );
                $allPermissions[] = $perm->id;
            }
        }

        // Create roles
        foreach (self::INITIAL_ROLES as $roleData) {
            $role = Role::firstOrCreate(['name' => $roleData['name']], $roleData);

            // Grant all permissions to Administrador
            if ($roleData['name'] === 'Administrador') {
                $role->permissions()->sync($allPermissions);
            }
        }
    }
}
