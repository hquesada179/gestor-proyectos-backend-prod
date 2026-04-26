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
}
