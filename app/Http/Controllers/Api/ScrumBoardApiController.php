<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrumBoardApiController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $proyecto = Proyecto::find($id);

        if (! $proyecto || ! $proyecto->isAccessibleBy($request->user()->id)) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $statuses = TaskStatus::orderBy('orden')->get();

        $tasks = $proyecto->tasks()
            ->with(['status', 'assignedTo', 'sprint'])
            ->get()
            ->groupBy('task_status_id');

        $columnas = $statuses->map(fn ($status) => [
            'id'     => $status->id,
            'nombre' => $status->nombre,
            'color'  => $status->color,
            'orden'  => $status->orden,
            'tareas' => ($tasks[$status->id] ?? collect())->map(fn ($t) => [
                'id'            => $t->id,
                'titulo'        => $t->titulo,
                'descripcion'   => $t->descripcion,
                'sprint_id'     => $t->sprint_id,
                'sprint_nombre' => $t->sprint?->nombre,
                'asignado_a'    => $t->assignedTo?->name,
                'fecha_limite'  => $t->fecha_limite?->toDateString(),
            ])->values(),
        ]);

        return response()->json([
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'columnas'        => $columnas,
        ]);
    }
}
