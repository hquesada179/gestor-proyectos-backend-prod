<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user      = $request->user()->load('aiCredit');
        $userId    = $user->id;

        $proyectoIds = Proyecto::accessibleBy($userId)->pluck('id');

        $totalProyectos   = $proyectoIds->count();
        $sprintsActivos   = Sprint::whereIn('proyecto_id', $proyectoIds)
            ->where('estado', 'activo')
            ->count();
        $tareasPendientes = Task::whereIn('proyecto_id', $proyectoIds)
            ->whereHas('status', fn ($q) => $q->where('nombre', '!=', 'Completado'))
            ->count();

        $proyectosRecientes = Proyecto::accessibleBy($userId)
            ->withCount(['tasks', 'sprints'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'nombre'        => $p->nombre,
                'estado'        => $p->estado,
                'tasks_count'   => $p->tasks_count,
                'sprints_count' => $p->sprints_count,
                'fecha_inicio'  => $p->fecha_inicio?->toDateString(),
            ]);

        return response()->json([
            'total_proyectos'     => $totalProyectos,
            'tareas_pendientes'   => $tareasPendientes,
            'sprints_activos'     => $sprintsActivos,
            'proyectos_recientes' => $proyectosRecientes,
            'ai_credits'          => $user->aiCredit ? [
                'available' => $user->aiCredit->credits_available,
                'used'      => $user->aiCredit->credits_used,
            ] : null,
        ]);
    }
}
