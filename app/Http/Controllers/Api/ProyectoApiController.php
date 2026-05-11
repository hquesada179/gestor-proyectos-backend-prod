<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProyectoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $proyectos = Proyecto::accessibleBy(Auth::id())
            ->withCount(['tasks', 'sprints'])
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'estado'      => ['nullable', 'string', 'in:activo,pausado,completado,cancelado'],
            'fecha_inicio'=> ['nullable', 'date_format:Y-m-d'],
            'fecha_fin'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (
            !empty($validated['fecha_inicio']) &&
            !empty($validated['fecha_fin']) &&
            $validated['fecha_fin'] < $validated['fecha_inicio']
        ) {
            throw ValidationException::withMessages([
                'fecha_fin' => ['La fecha de fin no puede ser anterior a la fecha de inicio.'],
            ]);
        }

        try {
            $proyecto = Auth::user()->proyectos()->create([
                'nombre'             => $validated['nombre'],
                'descripcion'        => $validated['descripcion'] ?? null,
                'estado'             => $validated['estado'] ?? 'activo',
                'fecha_inicio'       => $validated['fecha_inicio'] ?? null,
                'fecha_fin_estimada' => $validated['fecha_fin'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al crear el proyecto.'], 500);
        }

        return response()->json([
            'message' => 'Proyecto creado correctamente',
            'project' => [
                'id'                 => $proyecto->id,
                'nombre'             => $proyecto->nombre,
                'descripcion'        => $proyecto->descripcion,
                'estado'             => $proyecto->estado,
                'fecha_inicio'       => $proyecto->fecha_inicio?->toDateString(),
                'fecha_fin_estimada' => $proyecto->fecha_fin_estimada?->toDateString(),
            ],
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())
            ->withCount(['tasks', 'sprints'])
            ->with([
                'sprints' => fn($q) => $q->withCount('tasks')->orderBy('nombre'),
                'tasks.status',
            ])
            ->find($id);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
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
