<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TareaApiController extends Controller
{
    public function index(int $id): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($id);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $tareas = Task::with(['status', 'sprint', 'assignedTo'])
            ->where('proyecto_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(Task $t) => [
                'id'           => $t->id,
                'proyecto_id'  => $t->proyecto_id,
                'titulo'       => $t->titulo,
                'descripcion'  => $t->descripcion,
                'estado'       => $t->status?->nombre,
                'estado_color' => $t->status?->color,
                'sprint_id'    => $t->sprint_id,
                'sprint_nombre'=> $t->sprint?->nombre,
                'asignado_a'   => $t->assignedTo?->name,
                'fecha_limite' => $t->fecha_limite?->toDateString(),
                'created_at'   => $t->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'total'           => $tareas->count(),
            'tareas'          => $tareas,
        ]);
    }

    public function all(): JsonResponse
    {
        $proyectoIds = Proyecto::accessibleBy(Auth::id())->pluck('id');

        $tareas = Task::with(['status', 'sprint', 'assignedTo', 'proyecto'])
            ->whereIn('proyecto_id', $proyectoIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(Task $t) => [
                'id'              => $t->id,
                'proyecto_id'     => $t->proyecto_id,
                'proyecto_nombre' => $t->proyecto?->nombre,
                'titulo'          => $t->titulo,
                'descripcion'     => $t->descripcion,
                'estado'          => $t->status?->nombre,
                'estado_color'    => $t->status?->color,
                'sprint_id'       => $t->sprint_id,
                'sprint_nombre'   => $t->sprint?->nombre,
                'asignado_a'      => $t->assignedTo?->name,
                'fecha_limite'    => $t->fecha_limite?->toDateString(),
                'created_at'      => $t->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'total'  => $tareas->count(),
            'tareas' => $tareas,
        ]);
    }

    public function update(Request $request, int $tarea): JsonResponse
    {
        $task = Task::find($tarea);

        if (! $task) {
            return response()->json(['message' => 'Tarea no encontrada.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($task->proyecto_id);
        if (! $proyecto) {
            return response()->json(['message' => 'No tienes permiso para modificar esta tarea.'], 403);
        }

        $validated = $request->validate([
            'titulo'         => 'sometimes|string|max:255',
            'descripcion'    => 'sometimes|nullable|string',
            'estado'         => 'sometimes|in:pendiente,en_progreso,en_revision,completado',
            'sprint_id'      => 'sometimes|nullable|integer|exists:sprints,id',
            'responsable_id' => 'sometimes|nullable|integer|exists:users,id',
            'fecha_limite'   => 'sometimes|nullable|date',
        ]);

        // Mapper Android → task_statuses.nombre
        $estadoToNombre = [
            'pendiente'   => 'Pendiente',
            'en_progreso' => 'En progreso',
            'en_revision' => 'En revisión',
            'completado'  => 'Completado',
        ];

        $updates = [];

        if (isset($validated['estado'])) {
            $status = TaskStatus::where('nombre', $estadoToNombre[$validated['estado']])->first();
            if (! $status) {
                return response()->json(['message' => 'Estado no configurado en base de datos.'], 422);
            }
            $updates['task_status_id'] = $status->id;
        }

        if (isset($validated['titulo']))                    $updates['titulo']       = $validated['titulo'];
        if (array_key_exists('descripcion', $validated))    $updates['descripcion']  = $validated['descripcion'];
        if (array_key_exists('sprint_id', $validated))      $updates['sprint_id']    = $validated['sprint_id'];
        if (array_key_exists('responsable_id', $validated)) $updates['assigned_to']  = $validated['responsable_id'];
        if (array_key_exists('fecha_limite', $validated))   $updates['fecha_limite'] = $validated['fecha_limite'];

        $task->update($updates);
        $task->load(['status', 'sprint', 'assignedTo']);

        // Mapper inverso DB nombre → clave Android
        $nombreToEstado = [
            'Pendiente'   => 'pendiente',
            'En progreso' => 'en_progreso',
            'En revisión' => 'en_revision',
            'Completado'  => 'completado',
        ];

        return response()->json([
            'message' => 'Tarea actualizada correctamente',
            'data'    => [
                'id'             => $task->id,
                'proyecto_id'    => $task->proyecto_id,
                'titulo'         => $task->titulo,
                'descripcion'    => $task->descripcion,
                'estado'         => $nombreToEstado[$task->status?->nombre] ?? null,
                'estado_label'   => $task->status?->nombre,
                'sprint_id'      => $task->sprint_id,
                'responsable_id' => $task->assigned_to,
                'fecha_limite'   => $task->fecha_limite?->toDateString(),
                'updated_at'     => $task->updated_at?->toDateTimeString(),
            ],
        ]);
    }
}
