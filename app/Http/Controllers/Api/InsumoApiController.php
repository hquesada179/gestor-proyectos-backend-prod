<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
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

        $insumos = Insumo::where('proyecto_id', $project)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $insumos->map(fn(Insumo $i) => $this->formatInsumo($i));

        return response()->json([
            'data'    => $data,
            'summary' => [
                'total'       => $insumos->count(),
                'activos'     => $insumos->where('estado', 'activo')->count(),
                'agotados'    => $insumos->where('estado', 'agotado')->count(),
                'costo_total' => $insumos->sum('costo'),
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
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'tipo'        => 'sometimes|nullable|string|max:100',
            'cantidad'    => 'sometimes|nullable|numeric|min:0',
            'unidad'      => 'sometimes|nullable|string|max:50',
            'costo'       => 'sometimes|nullable|numeric|min:0',
            'proveedor'   => 'sometimes|nullable|string|max:255',
            'estado'      => 'sometimes|nullable|string|max:50',
        ]);

        $insumo = Insumo::create(array_merge($validated, ['proyecto_id' => $project]));

        return response()->json([
            'message' => 'Insumo creado correctamente',
            'data'    => $this->formatInsumo($insumo),
        ], 201);
    }

    public function show(int $insumo): JsonResponse
    {
        $item = Insumo::find($insumo);

        if (! $item) {
            return response()->json(['message' => 'Insumo no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($item->proyecto_id);
        if (! $proyecto) {
            return response()->json(['message' => 'No tienes permiso para ver este insumo.'], 403);
        }

        return response()->json(['data' => $this->formatInsumo($item)]);
    }

    public function update(Request $request, int $insumo): JsonResponse
    {
        $item = Insumo::find($insumo);

        if (! $item) {
            return response()->json(['message' => 'Insumo no encontrado.'], 404);
        }

        $proyecto = Proyecto::accessibleBy(Auth::id())->find($item->proyecto_id);
        if (! $proyecto) {
            return response()->json(['message' => 'No tienes permiso para modificar este insumo.'], 403);
        }

        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'tipo'        => 'sometimes|nullable|string|max:100',
            'cantidad'    => 'sometimes|nullable|numeric|min:0',
            'unidad'      => 'sometimes|nullable|string|max:50',
            'costo'       => 'sometimes|nullable|numeric|min:0',
            'proveedor'   => 'sometimes|nullable|string|max:255',
            'estado'      => 'sometimes|nullable|string|max:50',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Insumo actualizado correctamente',
            'data'    => $this->formatInsumo($item->fresh()),
        ]);
    }

    public function destroy(int $insumo): JsonResponse
    {
        $item = Insumo::find($insumo);

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

    private function formatInsumo(Insumo $i): array
    {
        return [
            'id'          => $i->id,
            'project_id'  => $i->proyecto_id,
            'nombre'      => $i->nombre,
            'name'        => $i->nombre,
            'descripcion' => $i->descripcion,
            'description' => $i->descripcion,
            'tipo'        => $i->tipo,
            'type'        => $i->tipo,
            'cantidad'    => $i->cantidad,
            'quantity'    => $i->cantidad,
            'unidad'      => $i->unidad,
            'unit'        => $i->unidad,
            'costo'       => $i->costo,
            'cost'        => $i->costo,
            'proveedor'   => $i->proveedor,
            'supplier'    => $i->proveedor,
            'estado'      => $i->estado,
            'status'      => $i->estado,
            'created_at'  => $i->created_at?->toDateTimeString(),
            'updated_at'  => $i->updated_at?->toDateTimeString(),
        ];
    }
}
