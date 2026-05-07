<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AiIntentService
{
    /**
     * Ordered keyword map for intent detection WITHOUT active project.
     * Checked top-to-bottom; first match wins.
     */
    private const PATTERNS = [
        'editar_proyecto' => [
            'mejorar', 'editar', 'modificar', 'actualizar', 'actualiza',
            'cambiar', 'cambia', 'añadir a', 'agregar a', 'extender', 'ampliar',
            'refactorizar', 'renovar', 'optimizar', 'ajustar', 'renombra', 'rename',
            'mejora', 'reemplaza', 'sustituye',
        ],
        'crear_proyecto' => [
            'quiero crear', 'crear proyecto', 'crear una app', 'crear una aplicación',
            'crear un sistema', 'nuevo proyecto', 'nueva app', 'nueva aplicación',
            'nueva plataforma', 'construir', 'desarrollar', 'hacer una app',
            'hacer un sistema', 'quiero una app', 'quiero un sistema',
            'quiero una plataforma', 'necesito una app', 'necesito un sistema',
        ],
        'generar_requerimientos' => [
            'requerimientos', 'requisitos', 'requirements', 'especificaciones',
            'casos de uso', 'historias de usuario',
        ],
        'generar_tareas' => [
            'tareas', 'tasks', 'actividades', 'listado de tareas', 'to-do',
        ],
        'generar_sprints' => [
            'sprints', 'sprint', 'iteraciones', 'iteración', 'planificación ágil',
        ],
        'generar_insumos' => [
            'insumos', 'recursos', 'materiales', 'presupuesto', 'herramientas',
            'licencias', 'equipos',
        ],
    ];

    /**
     * Detect intent from a user message (no active project context).
     */
    public function detect(string $message): string
    {
        $lower = mb_strtolower($message);

        foreach (self::PATTERNS as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    Log::debug('AiIntentService: detected intent', [
                        'intent'  => $intent,
                        'trigger' => $keyword,
                    ]);
                    return $intent;
                }
            }
        }

        return 'consulta_general';
    }

    /**
     * Detect intent when the user already has an active project selected.
     *
     * Priority (most specific first):
     *   mover_tareas_estado → eliminar_tareas → actualizar_tareas
     *   → generar_* → editar_proyecto (default)
     *
     * Key rules:
     * - NEVER returns crear_proyecto (project is already selected).
     * - Task-specific intents require a task reference (keyword or "Task N" pattern).
     */
    public function detectWithContext(string $message, bool $hasProject = false): string
    {
        if (!$hasProject) {
            return $this->detect($message);
        }

        $lower = mb_strtolower($message);

        // Is the prompt referencing a task?
        $taskKw      = ['tarea', 'tareas', 'task', 'tasks'];
        $hasTaskKw   = (bool) array_filter($taskKw, fn($k) => str_contains($lower, $k));
        $hasTaskN    = (bool) preg_match('/\btask\s*\d+\b/i', $message); // "Task 1", "Task 12"
        $hasTaskRef  = $hasTaskKw || $hasTaskN;

        // ── 1. Move tasks to a different status ───────────────────────────
        $moveKw = [
            'mueve', 'mover', 'pasa', 'pasar', 'cambia el estado', 'cambia estado',
            'marca como', 'set status', 'move to', 'completada', 'completa',
            'done', 'finalizada', 'en progreso', 'in progress', 'to do', 'pendiente',
        ];
        if ($hasTaskRef && $this->matchesAny($lower, $moveKw)) {
            return 'mover_tareas_estado';
        }

        // ── 2. Delete tasks ───────────────────────────────────────────────
        $deleteKw = [
            'elimina', 'eliminar', 'borra', 'borrar', 'quita', 'quitar',
            'descarta', 'descartar', 'remove', 'delete',
        ];
        if ($hasTaskRef && $this->matchesAny($lower, $deleteKw)) {
            return 'eliminar_tareas';
        }

        // ── 3. Update/rename existing tasks ──────────────────────────────
        $updateKw = [
            'cambia', 'cambie', 'renombra', 'rename', 'actualiza', 'modifica',
            'corrige', 'edita', 'mejora', 'reemplaza', 'sustituye', 'ponle', 'dale',
            'nombres más claros', 'nombre más claro', 'nombre claro', 'mejor nombre',
            'nombre descriptivo', 'nombre significativo', 'nombre coherente',
            'llamarse', 'debería llamar', 'deberia llamar',
        ];
        if ($hasTaskRef && $this->matchesAny($lower, $updateKw)) {
            return 'actualizar_tareas';
        }
        // "Task N should be named X" pattern without explicit verb
        if ($hasTaskN && $this->matchesAny($lower, ['nombre', 'llam', 'descri', 'mejor', 'claro'])) {
            return 'actualizar_tareas';
        }

        // ── 4. Generate new items ─────────────────────────────────────────
        foreach (['requerimientos', 'requisitos', 'requirements', 'especificaciones'] as $kw) {
            if (str_contains($lower, $kw)) return 'generar_requerimientos';
        }
        foreach (['tareas', 'tasks', 'actividades', 'to-do'] as $kw) {
            if (str_contains($lower, $kw)) return 'generar_tareas';
        }
        foreach (['sprint', 'sprints', 'iteracion', 'iteraciones'] as $kw) {
            if (str_contains($lower, $kw)) return 'generar_sprints';
        }
        foreach (['insumos', 'recursos', 'herramientas', 'materiales', 'presupuesto'] as $kw) {
            if (str_contains($lower, $kw)) return 'generar_insumos';
        }

        // Default: general project edit/improve
        return 'editar_proyecto';
    }

    /**
     * Returns true if the intent suggests the user is referencing an existing project
     * and might create duplicates.
     */
    public function isProjectRelated(string $intent): bool
    {
        return in_array($intent, ['crear_proyecto', 'editar_proyecto'], true);
    }

    /**
     * Returns true for intents that REQUIRE an active project to be useful.
     */
    public function requiresActiveProject(string $intent): bool
    {
        return in_array($intent, [
            'actualizar_tareas', 'eliminar_tareas', 'mover_tareas_estado',
        ], true);
    }

    /**
     * Human-readable label for each intent.
     */
    public function label(string $intent): string
    {
        return match ($intent) {
            'crear_proyecto'         => 'Crear proyecto',
            'editar_proyecto'        => 'Editar proyecto',
            'actualizar_tareas'      => 'Actualizar tareas',
            'eliminar_tareas'        => 'Eliminar tareas',
            'mover_tareas_estado'    => 'Cambiar estado de tareas',
            'generar_requerimientos' => 'Generar requerimientos',
            'generar_tareas'         => 'Generar tareas',
            'generar_sprints'        => 'Generar sprints',
            'generar_insumos'        => 'Generar insumos',
            default                  => 'Consulta general',
        };
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }
}
