<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;

class SprintApiController extends Controller
{
    public function index(int $id): JsonResponse
    {
        $proyecto = Proyecto::find($id);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $sprints = Sprint::withCount('tasks')
            ->where('proyecto_id', $id)
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn(Sprint $s) => [
                'id'          => $s->id,
                'proyecto_id' => $s->proyecto_id,
                'nombre'      => $s->nombre,
                'objetivo'    => $s->objetivo,
                'estado'      => $s->estado,
                'fecha_inicio'=> $s->fecha_inicio?->toDateString(),
                'fecha_fin'   => $s->fecha_fin?->toDateString(),
                'tasks_count' => $s->tasks_count,
                'created_at'  => $s->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'total'           => $sprints->count(),
            'sprints'         => $sprints,
        ]);
    }
}
