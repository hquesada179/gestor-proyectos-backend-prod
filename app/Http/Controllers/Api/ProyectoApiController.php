<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProyectoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $proyectos = Proyecto::accessibleBy(Auth::id())
            ->withCount(['tasks', 'sprints'])
            ->latest()
            ->get()
            ->map(fn(Proyecto $p) => $this->formatProject($p));

        return response()->json($proyectos);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->normalizedProjectPayload($request);
        $image = $this->projectImageFile($request);
        if ($image) {
            $payload['cover_image'] = $image;
        }

        $validator = Validator::make($payload, $this->projectRules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos del proyecto no son validos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (
            !empty($validated['fecha_inicio']) &&
            !empty($validated['fecha_fin_estimada']) &&
            $validated['fecha_fin_estimada'] < $validated['fecha_inicio']
        ) {
            throw ValidationException::withMessages([
                'fecha_fin_estimada' => ['La fecha de fin no puede ser anterior a la fecha de inicio.'],
            ]);
        }

        $storedImage = null;

        try {
            $data = [
                'nombre'             => $validated['nombre'],
                'descripcion'        => $validated['descripcion'] ?? null,
                'estado'             => $validated['estado'] ?? 'activo',
                'fecha_inicio'       => $validated['fecha_inicio'] ?? null,
                'fecha_fin_estimada' => $validated['fecha_fin_estimada'] ?? null,
            ];

            if (!empty($validated['cover_image'])) {
                $storedImage = $validated['cover_image']->store('projects', 'public');
                $data['cover_image'] = $storedImage;
            }

            $proyecto = Auth::user()->proyectos()->create($data);
        } catch (\Throwable $e) {
            if ($storedImage && Storage::disk('public')->exists($storedImage)) {
                Storage::disk('public')->delete($storedImage);
            }

            return response()->json(['message' => 'Error al crear el proyecto.'], 500);
        }

        return response()->json([
            'message' => 'Proyecto creado correctamente',
            'project' => $this->formatProject($proyecto),
            'data'    => $this->formatProject($proyecto),
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

        return response()->json(array_merge($this->formatProject($proyecto), [
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
        ]));
    }

    public function updateImage(Request $request, int $project): JsonResponse
    {
        $proyecto = $this->findAccessibleProject($project);
        if ($proyecto instanceof JsonResponse) {
            return $proyecto;
        }

        $image = $this->projectImageFile($request);

        $validator = Validator::make(['image' => $image], [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'La imagen del proyecto no es valida.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($proyecto->cover_image && Storage::disk('public')->exists($proyecto->cover_image)) {
            Storage::disk('public')->delete($proyecto->cover_image);
        }

        $proyecto->update([
            'cover_image' => $image->store('projects', 'public'),
        ]);

        return response()->json([
            'message' => 'Imagen del proyecto actualizada correctamente',
            'data'    => $this->formatProject($proyecto->fresh()),
        ]);
    }

    public function deleteImage(int $project): JsonResponse
    {
        $proyecto = $this->findAccessibleProject($project);
        if ($proyecto instanceof JsonResponse) {
            return $proyecto;
        }

        if ($proyecto->cover_image && Storage::disk('public')->exists($proyecto->cover_image)) {
            Storage::disk('public')->delete($proyecto->cover_image);
        }

        $proyecto->update(['cover_image' => null]);

        return response()->json([
            'message' => 'Imagen del proyecto eliminada correctamente',
            'data'    => $this->formatProject($proyecto->fresh()),
        ]);
    }

    private function formatProject(Proyecto $project): array
    {
        $imageUrl = $project->cover_image
            ? Storage::disk('public')->url($project->cover_image)
            : null;

        return [
            'id'                 => $project->id,
            'name'               => $project->nombre,
            'nombre'             => $project->nombre,
            'description'        => $project->descripcion,
            'descripcion'        => $project->descripcion,
            'status'             => $project->estado,
            'estado'             => $project->estado,
            'fecha_inicio'       => $project->fecha_inicio?->toDateString(),
            'start_date'         => $project->fecha_inicio?->toDateString(),
            'fecha_fin_estimada' => $project->fecha_fin_estimada?->toDateString(),
            'end_date'           => $project->fecha_fin_estimada?->toDateString(),
            'cover_image'        => $project->cover_image,
            'image'              => $imageUrl,
            'imagen'             => $imageUrl,
            'image_url'          => $imageUrl,
            'cover_url'          => $imageUrl,
            'thumbnail_url'      => $imageUrl,
            'tasks_count'        => $project->tasks_count ?? null,
            'sprints_count'      => $project->sprints_count ?? null,
        ];
    }

    private function normalizedProjectPayload(Request $request): array
    {
        $payload = $request->all();

        $aliases = [
            'name'        => 'nombre',
            'description' => 'descripcion',
            'status'      => 'estado',
            'start_date'  => 'fecha_inicio',
            'end_date'    => 'fecha_fin_estimada',
            'fecha_fin'   => 'fecha_fin_estimada',
        ];

        foreach ($aliases as $alias => $field) {
            if (!array_key_exists($field, $payload) && array_key_exists($alias, $payload)) {
                $payload[$field] = $payload[$alias];
            }
        }

        return array_intersect_key($payload, array_flip([
            'nombre',
            'descripcion',
            'estado',
            'fecha_inicio',
            'fecha_fin_estimada',
        ]));
    }

    private function projectRules(): array
    {
        return [
            'nombre'             => ['required', 'string', 'max:255'],
            'descripcion'        => ['nullable', 'string', 'max:5000'],
            'estado'             => ['nullable', 'string', 'in:activo,pausado,completado,cancelado'],
            'fecha_inicio'       => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin_estimada' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'cover_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function projectImageFile(Request $request)
    {
        foreach (['image', 'imagen', 'photo', 'cover', 'cover_image'] as $field) {
            if ($request->hasFile($field)) {
                return $request->file($field);
            }
        }

        return null;
    }

    private function findAccessibleProject(int $project): Proyecto|JsonResponse
    {
        $proyecto = Proyecto::find($project);

        if (! $proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $proyecto->isAccessibleBy(Auth::id())) {
            return response()->json(['message' => 'No tienes permiso para modificar este proyecto.'], 403);
        }

        return $proyecto;
    }
}
