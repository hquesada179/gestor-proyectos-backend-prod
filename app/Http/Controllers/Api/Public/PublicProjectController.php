<?php

namespace App\Http\Controllers\Api\Public;

use App\Contexts\ScrumCore\Application\UseCases\ListUserProjectsUseCase;
use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de la API pública para consulta de proyectos por clientes de terceros y CI/CD.
 */
class PublicProjectController extends Controller
{
    /**
     * Lista los proyectos accesibles para el portador del token.
     */
    public function index(ListUserProjectsUseCase $listUseCase): JsonResponse
    {
        // Se ejecuta el caso de uso del Bounded Context ScrumCore
        $proyectos = $listUseCase->execute((int) Auth::id())
            ->map(function (Proyecto $p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'estado' => $p->estado,
                    'fecha_inicio' => $p->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin_estimada' => $p->fecha_fin_estimada?->format('Y-m-d'),
                    'tasks_count' => $p->tasks_count ?? $p->tasks()->count(),
                    'sprints_count' => $p->sprints_count ?? $p->sprints()->count(),
                    'created_at' => $p->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $proyectos,
            'count' => $proyectos->count(),
        ]);
    }

    /**
     * Retorna detalle de un proyecto específico si el usuario tiene permiso.
     */
    public function show(int $id): JsonResponse
    {
        $proyecto = Proyecto::with(['sprints' => function ($q) {
            $q->where('estado', 'en_progreso')->latest();
        }])->find($id);

        if (! $proyecto) {
            // Early return si el recurso no existe
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        // Validación estricta de autorización mediante la regla de dominio accessibleBy
        if (! $proyecto->isAccessibleBy((int) Auth::id())) {
            return response()->json(['message' => 'Acceso no autorizado al proyecto solicitado.'], 403);
        }

        $activeSprint = $proyecto->sprints->first();

        return response()->json([
            'data' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'descripcion' => $proyecto->descripcion,
                'estado' => $proyecto->estado,
                'active_sprint' => $activeSprint ? [
                    'id' => $activeSprint->id,
                    'nombre' => $activeSprint->nombre,
                    'estado' => $activeSprint->estado,
                ] : null,
                'metrics' => [
                    'total_tasks' => $proyecto->tasks()->count(),
                    'total_requirements' => $proyecto->requirements()->count(),
                    'total_user_stories' => $proyecto->userStories()->count(),
                ],
                'created_at' => $proyecto->created_at?->toIso8601String(),
            ],
        ]);
    }
}
