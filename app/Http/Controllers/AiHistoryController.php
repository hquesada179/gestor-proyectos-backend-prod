<?php

namespace App\Http\Controllers;

use App\Models\AiProjectChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gestiona el historial de interacciones y propuestas del asistente de IA.
 */
class AiHistoryController extends Controller
{
    /**
     * Retorna el detalle completo de un registro de interacción con la IA.
     */
    public function historyDetail(int $id): JsonResponse
    {
        // Validamos la pertenencia del registro al usuario autenticado para evitar accesos no autorizados
        $record = AiProjectChat::where('id', $id)
            ->where('user_id', auth()->id())
            ->with('proyecto:id,nombre,estado,descripcion')
            ->first();

        // Si el registro no existe o no pertenece al usuario autenticado, retornamos 404
        if (! $record) {
            return response()->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
        }

        return response()->json([
            'ok' => true,
            'record' => [
                'id' => $record->id,
                'tipo_accion' => $record->tipo_accion,
                'modo' => $record->tipo_accion === 'crear_proyecto' ? 'crear' : 'mejorar',
                'modo_label' => $record->tipo_accion === 'crear_proyecto' ? 'Crear proyecto' : 'Mejorar proyecto',
                'prompt_usuario' => $record->prompt_usuario,
                'respuesta_ia' => $record->respuesta_ia,
                'datos_detectados' => $record->datos_detectados,
                'estado' => $record->estado,
                'proyecto_id' => $record->proyecto_id,
                'proyecto_nombre' => $record->proyecto?->nombre,
                'proyecto_estado' => $record->proyecto?->estado,
                'proyecto_url' => $record->proyecto_id ? route('proyectos.show', $record->proyecto_id) : null,
                'parent_chat_id' => $record->parent_chat_id,
                'created_at_full' => $record->created_at->format('d/m/Y H:i'),
                'created_at_human' => $record->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Lista los registros recientes de chat del usuario actual.
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proyecto_id' => ['nullable', 'integer'],
        ]);
        $proyectoId = $validated['proyecto_id'] ?? null;

        $query = AiProjectChat::where('user_id', auth()->id())
            ->with('proyecto:id,nombre');

        // Filtro opcional por proyecto si viene especificado en la consulta
        if ($proyectoId) {
            $query->where('proyecto_id', (int) $proyectoId);
        }

        // Límite defensivo a los últimos 50 registros para optimizar el consumo de memoria
        $records = $query->latest()->take(50)->get();

        return response()->json([
            'history' => $records->map(fn (AiProjectChat $r) => [
                'id' => $r->id,
                'tipo_accion' => $r->tipo_accion,
                'modo' => $r->tipo_accion === 'crear_proyecto' ? 'Crear proyecto' : 'Mejorar proyecto',
                'prompt_usuario' => Str::limit($r->prompt_usuario, 100),
                'prompt_completo' => $r->prompt_usuario,
                'estado' => $r->estado,
                'proyecto_id' => $r->proyecto_id,
                'proyecto_nombre' => $r->proyecto?->nombre,
                'proyecto_url' => $r->proyecto_id ? route('proyectos.show', $r->proyecto_id) : null,
                'created_at_human' => $r->created_at->diffForHumans(),
                'created_at_full' => $r->created_at->format('d/m/Y H:i'),
                'credits_cost' => match ($r->tipo_accion) {
                    'crear_proyecto' => 10,
                    'editar_proyecto' => 8,
                    default => 0,
                },
            ]),
        ]);
    }

    /**
     * Elimina un elemento específico del historial.
     */
    public function deleteHistoryItem(Request $request, int $id): JsonResponse
    {
        // Se valida que solo el propietario de la consulta pueda eliminarla
        $record = AiProjectChat::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        // Control temprano si el registro no existe
        if (! $record) {
            return response()->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
        }

        $record->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Limpia el historial del usuario, con opción de filtrar por proyecto.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proyecto_id' => ['nullable', 'integer'],
        ]);
        $proyectoId = $validated['proyecto_id'] ?? null;
        $query = AiProjectChat::where('user_id', auth()->id());

        // Si se provee un proyecto específico, restringimos el borrado a dicho proyecto
        if ($proyectoId) {
            $query->where('proyecto_id', (int) $proyectoId);
        }

        $deleted = $query->delete();

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }
}
