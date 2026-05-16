<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Requirement;
use App\Models\UserStory;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserStoryApiController extends Controller
{
    public function index(int $projectId, int $requirementId): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($projectId);
        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $requirement = Requirement::find($requirementId);
        if (! $requirement || $requirement->proyecto_id !== $projectId) {
            return response()->json(['message' => 'Requerimiento no encontrado en este proyecto.'], 404);
        }

        $stories = $requirement->userStories()->latest()->get();

        return response()->json([
            'data'    => $stories->map(fn(UserStory $s) => $this->format($s, $projectId)),
            'summary' => [
                'total' => $stories->count(),
                'alta'  => $stories->where('prioridad', 'alta')->count(),
                'media' => $stories->where('prioridad', 'media')->count(),
                'baja'  => $stories->where('prioridad', 'baja')->count(),
            ],
        ]);
    }

    public function show(int $projectId, int $requirementId, int $userStoryId): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($projectId);
        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $requirement = Requirement::find($requirementId);
        if (! $requirement || $requirement->proyecto_id !== $projectId) {
            return response()->json(['message' => 'Requerimiento no encontrado en este proyecto.'], 404);
        }

        $story = UserStory::find($userStoryId);
        if (! $story || $story->requirement_id !== $requirementId) {
            return response()->json(['message' => 'Historia de usuario no encontrada.'], 404);
        }

        return response()->json(['data' => $this->format($story, $projectId)]);
    }

    public function store(Request $request, int $projectId, int $requirementId): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($projectId);
        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $requirement = Requirement::find($requirementId);
        if (! $requirement || $requirement->proyecto_id !== $projectId) {
            return response()->json(['message' => 'Requerimiento no encontrado en este proyecto.'], 404);
        }

        $request->merge($this->resolveAliases($request->all()));

        $validated = $request->validate([
            'titulo'               => ['required', 'string', 'max:255'],
            'como_usuario'         => ['nullable', 'string', 'max:500'],
            'quiero'               => ['nullable', 'string', 'max:500'],
            'para_poder'           => ['nullable', 'string', 'max:500'],
            'criterios_aceptacion' => ['nullable', 'string', 'max:5000'],
            'prioridad'            => ['nullable', 'string', 'in:alta,media,baja'],
        ]);

        $story = $requirement->userStories()->create($validated);

        ProjectActivityLogger::log(
            $proyecto, 'created', 'historias',
            "Historia de usuario creada: «{$story->titulo}» en requerimiento «{$requirement->titulo}»",
            null, $story
        );

        return response()->json([
            'message' => 'Historia de usuario creada correctamente.',
            'data'    => $this->format($story, $projectId),
        ], 201);
    }

    public function update(Request $request, int $projectId, int $requirementId, int $userStoryId): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($projectId);
        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $requirement = Requirement::find($requirementId);
        if (! $requirement || $requirement->proyecto_id !== $projectId) {
            return response()->json(['message' => 'Requerimiento no encontrado en este proyecto.'], 404);
        }

        $story = UserStory::find($userStoryId);
        if (! $story || $story->requirement_id !== $requirementId) {
            return response()->json(['message' => 'Historia de usuario no encontrada.'], 404);
        }

        $request->merge($this->resolveAliases($request->all()));

        $validated = $request->validate([
            'titulo'               => ['sometimes', 'required', 'string', 'max:255'],
            'como_usuario'         => ['nullable', 'string', 'max:500'],
            'quiero'               => ['nullable', 'string', 'max:500'],
            'para_poder'           => ['nullable', 'string', 'max:500'],
            'criterios_aceptacion' => ['nullable', 'string', 'max:5000'],
            'prioridad'            => ['nullable', 'string', 'in:alta,media,baja'],
        ]);

        $story->update($validated);

        return response()->json([
            'message' => 'Historia de usuario actualizada correctamente.',
            'data'    => $this->format($story->fresh(), $projectId),
        ]);
    }

    public function destroy(int $projectId, int $requirementId, int $userStoryId): JsonResponse
    {
        $proyecto = Proyecto::accessibleBy(Auth::id())->find($projectId);
        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado o sin acceso.'], 404);
        }

        $requirement = Requirement::find($requirementId);
        if (! $requirement || $requirement->proyecto_id !== $projectId) {
            return response()->json(['message' => 'Requerimiento no encontrado en este proyecto.'], 404);
        }

        $story = UserStory::find($userStoryId);
        if (! $story || $story->requirement_id !== $requirementId) {
            return response()->json(['message' => 'Historia de usuario no encontrada.'], 404);
        }

        $titulo = $story->titulo;
        ProjectActivityLogger::log(
            $proyecto, 'deleted', 'historias',
            "Historia de usuario eliminada: «{$titulo}» del requerimiento «{$requirement->titulo}»"
        );

        $story->delete();

        return response()->json(['message' => 'Historia de usuario eliminada correctamente.']);
    }

    private function format(UserStory $s, int $projectId): array
    {
        return [
            'id'                   => $s->id,
            'project_id'           => $projectId,
            'requirement_id'       => $s->requirement_id,
            'titulo'               => $s->titulo,
            'title'                => $s->titulo,
            'como_usuario'         => $s->como_usuario,
            'as_user'              => $s->como_usuario,
            'quiero'               => $s->quiero,
            'want'                 => $s->quiero,
            'para_poder'           => $s->para_poder,
            'so_that'              => $s->para_poder,
            'criterios_aceptacion' => $s->criterios_aceptacion,
            'acceptance_criteria'  => $s->criterios_aceptacion,
            'prioridad'            => $s->prioridad,
            'priority'             => $s->prioridad,
            'created_at'           => $s->created_at?->toDateTimeString(),
            'updated_at'           => $s->updated_at?->toDateTimeString(),
        ];
    }

    private function resolveAliases(array $input): array
    {
        foreach ([
            'title'               => 'titulo',
            'as_user'             => 'como_usuario',
            'want'                => 'quiero',
            'so_that'             => 'para_poder',
            'acceptance_criteria' => 'criterios_aceptacion',
            'priority'            => 'prioridad',
        ] as $alias => $field) {
            if (array_key_exists($alias, $input) && ! array_key_exists($field, $input)) {
                $input[$field] = $input[$alias];
            }
        }

        return $input;
    }
}
