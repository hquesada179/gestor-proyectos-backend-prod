<?php

namespace App\Http\Controllers\Api\Public;

use App\Contexts\ScrumCore\Domain\Ports\TaskRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de la API pública para consulta y mutación de tareas desde herramientas externas.
 */
class PublicTaskController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * Lista las tareas de un proyecto para integración o consulta CLI.
     */
    public function index(int $projectId): JsonResponse
    {
        $proyecto = Proyecto::find($projectId);

        if (! $proyecto) {
            // Early return si no se localiza el proyecto padre
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        // Validación de pertenencia del usuario al proyecto
        if (! $proyecto->isAccessibleBy((int) Auth::id())) {
            return response()->json(['message' => 'No autorizado para ver tareas de este proyecto.'], 403);
        }

        $tasks = $this->taskRepository->listByProject($projectId)
            ->map(function (Task $t) {
                return [
                    'id' => $t->id,
                    'titulo' => $t->titulo,
                    'descripcion' => $t->descripcion,
                    'status' => $t->status?->nombre ?? 'Sin estado',
                    'status_id' => $t->task_status_id,
                    'assigned_to' => $t->assignedTo?->name,
                    'fecha_limite' => $t->fecha_limite?->format('Y-m-d'),
                ];
            });

        return response()->json([
            'project_id' => $projectId,
            'count' => $tasks->count(),
            'data' => $tasks,
        ]);
    }

    /**
     * Crea una nueva tarea (ej. desde un commit de Git o una alerta de CI/CD).
     */
    public function store(Request $request, int $projectId): JsonResponse
    {
        $proyecto = Proyecto::find($projectId);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        // Verificación de pertenencia activa
        if (! $proyecto->isAccessibleBy((int) Auth::id())) {
            return response()->json(['message' => 'No autorizado para crear tareas en este proyecto.'], 403);
        }

        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'task_status_id' => ['nullable', 'exists:task_statuses,id'],
            'fecha_limite' => ['nullable', 'date'],
        ]);

        // Si no se especifica estado, se asigna el estado por defecto o se crea uno base si la tabla está vacía
        $defaultStatusId = $validated['task_status_id']
            ?? (TaskStatus::orderBy('orden')->value('id')
                ?? TaskStatus::create(['nombre' => 'Por hacer', 'orden' => 1, 'color' => '#6b7280'])->id);

        $task = Task::create([
            'proyecto_id' => $projectId,
            'task_status_id' => $defaultStatusId,
            'assigned_to' => Auth::id(),
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'fecha_limite' => $validated['fecha_limite'] ?? null,
        ]);

        // Registrar la actividad en el log de auditoría
        ProjectActivityLogger::log(
            $proyecto,
            'created',
            'tasks',
            "Tarea creada vía API Pública: «{$task->titulo}»",
            null,
            $task
        );

        return response()->json([
            'message' => 'Tarea creada exitosamente desde el canal público.',
            'data' => [
                'id' => $task->id,
                'titulo' => $task->titulo,
                'status_id' => $task->task_status_id,
                'created_at' => $task->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Actualiza el estado Kanban de una tarea (ej. marcar como Completada desde un pull request).
     */
    public function updateStatus(Request $request, int $taskId): JsonResponse
    {
        $task = $this->taskRepository->findById($taskId);

        if (! $task) {
            return response()->json(['message' => 'Tarea no encontrada.'], 404);
        }

        $proyecto = $task->proyecto;
        // Validación de scope de autorización sobre el proyecto asociado a la tarea
        if (! $proyecto || ! $proyecto->isAccessibleBy((int) Auth::id())) {
            return response()->json(['message' => 'No autorizado para modificar esta tarea.'], 403);
        }

        $validated = $request->validate([
            'task_status_id' => ['required', 'exists:task_statuses,id'],
        ]);

        $this->taskRepository->updateStatus($taskId, (int) $validated['task_status_id']);

        $newStatus = TaskStatus::find($validated['task_status_id']);

        return response()->json([
            'message' => 'Estado de tarea actualizado correctamente.',
            'task_id' => $taskId,
            'new_status' => $newStatus?->nombre,
        ]);
    }
}
