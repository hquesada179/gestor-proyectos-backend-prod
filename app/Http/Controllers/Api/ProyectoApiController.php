<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;

class ProyectoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $proyectos = Proyecto::withCount(['tasks', 'sprints'])
            ->latest()
            ->get()
            ->map(fn(Proyecto $p) => [
                'id'                 => $p->id,
                'nombre'             => $p->nombre,
                'descripcion'        => $p->descripcion,
                'estado'             => $p->estado,
                'fecha_inicio'       => $p->fecha_inicio?->toDateString(),
                'fecha_fin_estimada' => $p->fecha_fin_estimada?->toDateString(),
                'tasks_count'        => $p->tasks_count,
                'sprints_count'      => $p->sprints_count,
            ]);

        return response()->json($proyectos);
    }

    public function show(int $id): JsonResponse
    {
        $proyecto = Proyecto::withCount(['tasks', 'sprints'])
            ->with([
                'sprints' => fn($q) => $q->withCount('tasks')->orderBy('nombre'),
                'tasks.status',
            ])
            ->find($id);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        return response()->json([
            'id'                 => $proyecto->id,
            'nombre'             => $proyecto->nombre,
            'descripcion'        => $proyecto->descripcion,
            'estado'             => $proyecto->estado,
            'fecha_inicio'       => $proyecto->fecha_inicio?->toDateString(),
            'fecha_fin_estimada' => $proyecto->fecha_fin_estimada?->toDateString(),
            'tasks_count'        => $proyecto->tasks_count,
            'sprints_count'      => $proyecto->sprints_count,
            'sprints'            => $proyecto->sprints->map(fn($s) => [
                'id'          => $s->id,
                'nombre'      => $s->nombre,
                'objetivo'    => $s->objetivo,
                'estado'      => $s->estado,
                'fecha_inicio'=> $s->fecha_inicio?->toDateString(),
                'fecha_fin'   => $s->fecha_fin?->toDateString(),
                'tasks_count' => $s->tasks_count,
            ]),
            'tasks'              => $proyecto->tasks->map(fn($t) => [
                'id'          => $t->id,
                'titulo'      => $t->titulo,
                'descripcion' => $t->descripcion,
                'estado'      => $t->status?->nombre,
                'fecha_limite'=> $t->fecha_limite?->toDateString(),
                'sprint_id'   => $t->sprint_id,
            ]),
        ]);
    }
}
