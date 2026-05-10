<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
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
}
