<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Requirement;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequerimientoApiController extends Controller
{
    public function index(int $id): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($id);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $requirements = $proyecto->requirements()
            ->withCount('userStories')
            ->orderByRaw("CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 WHEN 'baja' THEN 3 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $requirements->map(fn(Requirement $r) => $this->format($r));

        return response()->json([
            'data'    => $data,
            'summary' => [
                'total'       => $requirements->count(),
                'completed'   => $requirements->where('estado', 'completado')->count(),
                'in_progress' => $requirements->where('estado', 'en_progreso')->count(),
                'pending'     => $requirements->where('estado', 'pendiente')->count(),
            ],
        ]);
    }

    public function show(int $requirement): JsonResponse
    {
        $req = Requirement::withCount('userStories')->find($requirement);

        if (! $req) {
            return response()->json(['message' => 'Requerimiento no encontrado.'], 404);
        }

        if (! Proyecto::accessibleBy(Auth::id())->find($req->proyecto_id)) {
            return response()->json(['message' => 'Sin acceso a este requerimiento.'], 403);
        }

        return response()->json(['data' => $this->format($req)]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($id);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $request->merge($this->resolveAliases($request->all()));

        $validated = $request->validate([
            'titulo'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo'        => ['nullable', 'string', 'in:funcional,no_funcional'],
            'prioridad'   => ['nullable', 'string', 'in:alta,media,baja'],
            'estado'      => ['nullable', 'string', 'in:pendiente,en_progreso,completado'],
            'codigo'      => ['nullable', 'string', 'max:50'],
        ]);

        $requirement = $proyecto->requirements()->create($validated);

        ProjectActivityLogger::log(
            $proyecto, 'created', 'requerimientos',
            "Requerimiento creado: «{$requirement->titulo}»", null, $requirement
        );

        return response()->json([
            'message' => 'Requerimiento creado correctamente.',
            'data'    => $this->format($requirement),
        ], 201);
    }

    public function update(Request $request, int $requirement): JsonResponse
    {
        $req = Requirement::find($requirement);

        if (! $req) {
            return response()->json(['message' => 'Requerimiento no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($req->proyecto_id);

        if (! $proyecto) {
            return response()->json(['message' => 'Sin acceso a este requerimiento.'], 403);
        }

        $request->merge($this->resolveAliases($request->all()));

        $validated = $request->validate([
            'titulo'      => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo'        => ['nullable', 'string', 'in:funcional,no_funcional'],
            'prioridad'   => ['nullable', 'string', 'in:alta,media,baja'],
            'estado'      => ['nullable', 'string', 'in:pendiente,en_progreso,completado'],
            'codigo'      => ['nullable', 'string', 'max:50'],
        ]);

        $oldValues = $req->only(['titulo', 'tipo', 'prioridad', 'descripcion', 'estado']);
        $req->update($validated);
        $newValues = $req->fresh()->only(['titulo', 'tipo', 'prioridad', 'descripcion', 'estado']);

        ProjectActivityLogger::log(
            $proyecto, 'updated', 'requerimientos',
            "Requerimiento editado: «{$req->titulo}»", null, $req, $oldValues, $newValues
        );

        return response()->json([
            'message' => 'Requerimiento actualizado correctamente.',
            'data'    => $this->format($req->fresh()),
        ]);
    }

    public function destroy(int $requirement): JsonResponse
    {
        $req = Requirement::find($requirement);

        if (! $req) {
            return response()->json(['message' => 'Requerimiento no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($req->proyecto_id);

        if (! $proyecto) {
            return response()->json(['message' => 'Sin acceso a este requerimiento.'], 403);
        }

        $titulo = $req->titulo;

        ProjectActivityLogger::log(
            $proyecto, 'deleted', 'requerimientos',
            "Requerimiento eliminado: «{$titulo}»"
        );

        $req->delete();

        return response()->json(['message' => 'Requerimiento eliminado correctamente.']);
    }

    public function updateStatus(Request $request, int $requirement): JsonResponse
    {
        $req = Requirement::find($requirement);

        if (! $req) {
            return response()->json(['message' => 'Requerimiento no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($req->proyecto_id);

        if (! $proyecto) {
            return response()->json(['message' => 'Sin acceso a este requerimiento.'], 403);
        }

        $validated = $request->validate([
            'estado' => ['required', 'string', 'in:pendiente,en_progreso,completado'],
        ]);

        $req->update(['estado' => $validated['estado']]);

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'data'    => $this->format($req->fresh()),
        ]);
    }

    private function format(Requirement $r): array
    {
        return [
            'id'                 => $r->id,
            'project_id'         => $r->proyecto_id,
            'proyecto_id'        => $r->proyecto_id,
            'codigo'             => $r->codigo,
            'code'               => $r->codigo,
            'titulo'             => $r->titulo,
            'title'              => $r->titulo,
            'descripcion'        => $r->descripcion,
            'description'        => $r->descripcion,
            'tipo'               => $r->tipo,
            'type'               => $r->tipo,
            'prioridad'          => $r->prioridad,
            'priority'           => $r->prioridad,
            'estado'             => $r->estado ?? 'pendiente',
            'status'             => $r->estado ?? 'pendiente',
            'user_stories_count' => $r->user_stories_count ?? 0,
            'created_at'         => $r->created_at?->toDateTimeString(),
            'updated_at'         => $r->updated_at?->toDateTimeString(),
        ];
    }

    private function resolveAliases(array $input): array
    {
        foreach ([
            'title'       => 'titulo',
            'description' => 'descripcion',
            'type'        => 'tipo',
            'priority'    => 'prioridad',
            'status'      => 'estado',
            'code'        => 'codigo',
        ] as $alias => $field) {
            if (array_key_exists($alias, $input) && ! array_key_exists($field, $input)) {
                $input[$field] = $input[$alias];
            }
        }

        return $input;
    }
}
