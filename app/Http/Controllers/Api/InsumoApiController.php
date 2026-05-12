<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInput;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsumoApiController extends Controller
{
    public function index(int $project): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $inputs = ProjectInput::where('proyecto_id', $project)
            ->latest()
            ->get();

        return response()->json([
            'data'    => $inputs->map(fn(ProjectInput $i) => $this->format($i)),
            'summary' => [
                'total'       => $inputs->count(),
                'activos'     => $inputs->count(),
                'agotados'    => 0,
                'costo_total' => 0,
            ],
        ]);
    }

    public function store(Request $request, int $project): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $validated = $request->validate([
            'nombre'      => 'required_without:name|string|max:255',
            'name'        => 'required_without:nombre|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string',
            'tipo'        => 'sometimes|nullable|string|max:100',
            'type'        => 'sometimes|nullable|string|max:100',
        ]);

        $input = ProjectInput::create([
            'proyecto_id' => $project,
            'titulo'      => $validated['nombre'] ?? $validated['name'],
            'contenido'   => $validated['descripcion'] ?? $validated['description'] ?? null,
            'tipo'        => $validated['tipo'] ?? $validated['type'] ?? 'otro',
        ]);

        return response()->json([
            'message' => 'Insumo creado correctamente',
            'data'    => $this->format($input),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = ProjectInput::find($id);

        if (! $item) {
            return response()->json(['message' => 'Insumo no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($item->proyecto_id);
        if (! $proyecto) {
            return response()->json(['message' => 'No tienes permiso para ver este insumo.'], 403);
        }

        return response()->json(['data' => $this->format($item)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectInput::find($id);

        if (! $item) {
            return response()->json(['message' => 'Insumo no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($item->proyecto_id);
        if (! $proyecto) {
            return response()->json(['message' => 'No tienes permiso para modificar este insumo.'], 403);
        }

        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:255',
            'name'        => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string',
            'tipo'        => 'sometimes|nullable|string|max:100',
            'type'        => 'sometimes|nullable|string|max:100',
        ]);

        if (isset($validated['nombre']) || isset($validated['name'])) {
            $item->titulo = $validated['nombre'] ?? $validated['name'];
        }
        if (array_key_exists('descripcion', $validated) || array_key_exists('description', $validated)) {
            $item->contenido = $validated['descripcion'] ?? $validated['description'];
        }
        if (isset($validated['tipo']) || isset($validated['type'])) {
            $item->tipo = $validated['tipo'] ?? $validated['type'];
        }

        $item->save();

        return response()->json([
            'message' => 'Insumo actualizado correctamente',
            'data'    => $this->format($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = ProjectInput::find($id);

        if (! $item) {
            return response()->json(['message' => 'Insumo no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($item->proyecto_id);
        if (! $proyecto) {
            return response()->json(['message' => 'No tienes permiso para eliminar este insumo.'], 403);
        }

        $item->delete();

        return response()->json(['message' => 'Insumo eliminado correctamente']);
    }

    private function format(ProjectInput $i): array
    {
        return [
            'id'          => $i->id,
            'project_id'  => $i->proyecto_id,
            'proyecto_id' => $i->proyecto_id,
            'nombre'      => $i->titulo,
            'name'        => $i->titulo,
            'descripcion' => $i->contenido,
            'description' => $i->contenido,
            'tipo'        => $i->tipo,
            'type'        => $i->tipo,
            'cantidad'    => null,
            'quantity'    => null,
            'unidad'      => null,
            'unit'        => null,
            'costo'       => null,
            'cost'        => null,
            'proveedor'   => null,
            'supplier'    => null,
            'estado'      => 'activo',
            'status'      => 'activo',
            'created_at'  => $i->created_at?->toDateTimeString(),
            'updated_at'  => $i->updated_at?->toDateTimeString(),
        ];
    }
}
