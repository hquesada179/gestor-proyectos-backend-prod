<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Proyecto;
use App\Models\Sprint;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CalendarEventApiController extends Controller
{
    private const TYPES = [
        'evento',
        'reunion',
        'entrega',
        'recordatorio',
        'tarea',
        'sprint',
        'proyecto',
        'otro',
    ];

    private const STATUSES = [
        'pendiente',
        'en_progreso',
        'completado',
        'cancelado',
    ];

    public function index(Request $request): JsonResponse
    {
        return $this->calendarResponse($request);
    }

    public function projectCalendar(Request $request, int $project): JsonResponse
    {
        return $this->calendarResponse($request, $project);
    }

    public function storeGlobal(Request $request): JsonResponse
    {
        $projectId = $request->input('project_id', $request->input('proyecto_id'));

        $validator = Validator::make(['project_id' => $projectId], [
            'project_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'El proyecto es obligatorio para crear un evento.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        return $this->storeForProject($request, (int) $projectId);
    }

    public function store(Request $request, int $project): JsonResponse
    {
        return $this->storeForProject($request, $project);
    }

    private function storeForProject(Request $request, int $project): JsonResponse
    {
        $proyecto = $this->findProjectForUser($project);
        if ($proyecto instanceof JsonResponse) {
            return $proyecto;
        }

        $validated = $this->validateEventPayload($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $event = CalendarEvent::create([
            'project_id'   => $proyecto->id,
            'user_id'      => Auth::id(),
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'type'         => $validated['type'] ?? 'evento',
            'status'       => $validated['status'] ?? 'pendiente',
            'start_at'     => $validated['start_at'] ?? null,
            'end_at'       => $validated['end_at'] ?? null,
            'all_day'      => (bool) ($validated['all_day'] ?? false),
            'source_type'  => null,
            'source_id'    => null,
        ]);

        $event->load('project:id,nombre');

        return response()->json([
            'message' => 'Evento creado correctamente',
            'data'    => $this->formatManualEvent($event),
        ], 201);
    }

    public function show(int $event): JsonResponse
    {
        $calendarEvent = CalendarEvent::with('project:id,nombre,user_id,estado,descripcion')
            ->find($event);

        if (! $calendarEvent) {
            return response()->json(['message' => 'Evento no encontrado.'], 404);
        }

        if (! $this->canAccessEvent($calendarEvent)) {
            return response()->json(['message' => 'No tienes permiso para ver este evento.'], 403);
        }

        return response()->json([
            'data' => $this->formatManualEvent($calendarEvent),
        ]);
    }

    public function update(Request $request, int $event): JsonResponse
    {
        $calendarEvent = CalendarEvent::with('project:id,nombre,user_id')
            ->find($event);

        if (! $calendarEvent) {
            return response()->json(['message' => 'Evento no encontrado.'], 404);
        }

        if (! $this->canAccessEvent($calendarEvent)) {
            return response()->json(['message' => 'No tienes permiso para modificar este evento.'], 403);
        }

        $validated = $this->validateEventPayload($request, true);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $calendarEvent->update($validated);
        $calendarEvent->load('project:id,nombre');

        return response()->json([
            'message' => 'Evento actualizado correctamente',
            'data'    => $this->formatManualEvent($calendarEvent),
        ]);
    }

    public function destroy(int $event): JsonResponse
    {
        $calendarEvent = CalendarEvent::with('project:id,nombre,user_id')
            ->find($event);

        if (! $calendarEvent) {
            return response()->json(['message' => 'Evento no encontrado.'], 404);
        }

        if (! $this->canAccessEvent($calendarEvent)) {
            return response()->json(['message' => 'No tienes permiso para eliminar este evento.'], 403);
        }

        $calendarEvent->delete();

        return response()->json([
            'message' => 'Evento eliminado correctamente',
        ]);
    }

    private function calendarResponse(Request $request, ?int $projectId = null): JsonResponse
    {
        $filters = $this->validateCalendarFilters($request);
        if ($filters instanceof JsonResponse) {
            return $filters;
        }

        $userId = Auth::id();
        $project = null;
        $requestedProjectId = $projectId ?: ($filters['project_id'] ?? null);

        if ($requestedProjectId) {
            $project = $this->findProjectForUser((int) $requestedProjectId);
            if ($project instanceof JsonResponse) {
                return $project;
            }
            $projectIds = collect([$project->id]);
        } else {
            $projectIds = Proyecto::accessibleBy($userId)->pluck('id');
        }

        // Default to current month when no range is provided
        $start = !empty($filters['start'])
            ? Carbon::parse($filters['start'])->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $end = !empty($filters['end'])
            ? Carbon::parse($filters['end'])->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        $type = $filters['type'] ?? null;

        $manualEvts  = $this->manualEvents($projectIds, $userId, $project?->id, $start, $end, $type);
        $sprintEvts  = $this->sprintEvents($projectIds, $start, $end, $type);
        $projectEvts = $this->projectEvents($projectIds, $start, $end, $type);
        $taskEvts    = $this->taskEvents($projectIds, $start, $end, $type);

        \Log::info('[CalendarApi] endpoint=' . request()->path(), [
            'start_param'         => $filters['start'] ?? null,
            'end_param'           => $filters['end'] ?? null,
            'start_used'          => $start->toDateString(),
            'end_used'            => $end->toDateString(),
            'manual_events_count' => $manualEvts->count(),
            'sprints_count'       => $sprintEvts->count(),
            'projects_count'      => $projectEvts->count(),
            'tasks_table'         => 'tasks',
            'tasks_count'         => $taskEvts->count(),
            'total_events_count'  => $manualEvts->count() + $sprintEvts->count() + $projectEvts->count() + $taskEvts->count(),
        ]);

        $events = collect()
            ->merge($manualEvts)
            ->merge($taskEvts)
            ->merge($sprintEvts)
            ->merge($projectEvts)
            ->sortBy(fn(array $event) => $event['start'] ?? $event['start_at'] ?? $event['date'] ?? '')
            ->values();

        return response()->json([
            'data'    => $events,
            'summary' => [
                'total'     => $events->count(),
                'tareas'    => $events->where('source', 'tarea')->count(),
                'sprints'   => $events->where('source', 'sprint')->count(),
                'eventos'   => $events->where('source', 'evento')->count(),
                'proyectos' => $events->where('source', 'proyecto')->count(),
            ],
            'project' => $project ? [
                'id'     => $project->id,
                'name'   => $project->nombre,
                'nombre' => $project->nombre,
            ] : null,
        ]);
    }

    private function manualEvents(
        $projectIds,
        int $userId,
        ?int $projectId,
        ?Carbon $start,
        ?Carbon $end,
        ?string $type
    ) {
        $query = CalendarEvent::with('project:id,nombre')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when(! $projectId, function ($q) use ($projectIds, $userId) {
                $q->where(function ($inner) use ($projectIds, $userId) {
                    $inner->where('user_id', $userId);

                    if ($projectIds->isNotEmpty()) {
                        $inner->orWhereIn('project_id', $projectIds);
                    }
                });
            })
            ->when($type && ! in_array($type, ['tarea', 'sprint', 'proyecto'], true), fn($q) => $q->where('type', $type));

        if ($type && in_array($type, ['tarea', 'sprint', 'proyecto'], true)) {
            return collect();
        }

        return $query->latest('start_at')
            ->get()
            ->filter(fn(CalendarEvent $event) => $this->eventOverlapsRange($event->start_at, $event->end_at, $start, $end))
            ->map(fn(CalendarEvent $event) => $this->formatManualEvent($event));
    }

    private function taskEvents($projectIds, ?Carbon $start, ?Carbon $end, ?string $type)
    {
        if ($type && $type !== 'tarea') {
            return collect();
        }

        return Task::with(['proyecto:id,nombre', 'status:id,nombre'])
            ->whereIn('proyecto_id', $projectIds)
            ->whereNotNull('fecha_limite')
            ->get()
            ->filter(fn(Task $task) => $this->dateInRange($task->fecha_limite, $start, $end))
            ->map(function (Task $task) {
                $startAt = $task->fecha_limite?->copy()->setTime(9, 0);

                return $this->baseEventPayload([
                    'id'           => 'task-' . $task->id,
                    'source_id'    => $task->id,
                    'source_type'  => 'tarea',
                    'project_id'   => $task->proyecto_id,
                    'project_name' => $task->proyecto?->nombre,
                    'title'        => $task->titulo,
                    'description'  => $task->descripcion,
                    'type'         => 'tarea',
                    'status'       => $this->normalizeStatus($task->status?->nombre),
                    'date'         => $task->fecha_limite?->toDateString(),
                    'start_at'     => $startAt?->toDateTimeString(),
                    'end_at'       => null,
                    'all_day'      => false,
                ]);
            });
    }

    private function sprintEvents($projectIds, ?Carbon $start, ?Carbon $end, ?string $type)
    {
        if ($type && $type !== 'sprint') {
            return collect();
        }

        return Sprint::with('proyecto:id,nombre')
            ->whereIn('proyecto_id', $projectIds)
            ->get()
            ->filter(function (Sprint $sprint) use ($start, $end) {
                // Include sprint if its period overlaps the filter range
                $sprintStart = $sprint->fecha_inicio ? Carbon::parse($sprint->fecha_inicio)->startOfDay() : null;
                $sprintEnd   = $sprint->fecha_fin   ? Carbon::parse($sprint->fecha_fin)->endOfDay()     : $sprintStart;

                return $this->eventOverlapsRange($sprintStart, $sprintEnd, $start, $end);
            })
            ->map(function (Sprint $sprint) {
                $sprintStart = $sprint->fecha_inicio ? Carbon::parse($sprint->fecha_inicio) : null;
                $sprintEnd   = $sprint->fecha_fin   ? Carbon::parse($sprint->fecha_fin)    : null;

                return $this->baseEventPayload([
                    'id'           => 'sprint-' . $sprint->id,
                    'source_id'    => $sprint->id,
                    'source_type'  => 'sprint',
                    'project_id'   => $sprint->proyecto_id,
                    'project_name' => $sprint->proyecto?->nombre,
                    'title'        => $sprint->nombre,
                    'description'  => $sprint->objetivo,
                    'type'         => 'sprint',
                    'status'       => $this->normalizeStatus($sprint->estado),
                    'date'         => $sprintStart?->toDateString(),
                    'start_at'     => $sprintStart?->startOfDay()->toDateTimeString(),
                    'end_at'       => $sprintEnd?->endOfDay()->toDateTimeString(),
                    'all_day'      => true,
                ]);
            });
    }

    private function projectEvents($projectIds, ?Carbon $start, ?Carbon $end, ?string $type)
    {
        if ($type && $type !== 'proyecto') {
            return collect();
        }

        return Proyecto::whereIn('id', $projectIds)
            ->get(['id', 'nombre', 'descripcion', 'estado', 'fecha_inicio', 'fecha_fin_estimada'])
            ->filter(function (Proyecto $project) use ($start, $end) {
                // Include project if its period overlaps the filter range
                $projStart = $project->fecha_inicio        ? Carbon::parse($project->fecha_inicio)->startOfDay()        : null;
                $projEnd   = $project->fecha_fin_estimada  ? Carbon::parse($project->fecha_fin_estimada)->endOfDay()    : $projStart;

                return $this->eventOverlapsRange($projStart, $projEnd, $start, $end);
            })
            ->map(function (Proyecto $project) {
                $projStart = $project->fecha_inicio       ? Carbon::parse($project->fecha_inicio)       : null;
                $projEnd   = $project->fecha_fin_estimada ? Carbon::parse($project->fecha_fin_estimada) : null;

                return $this->baseEventPayload([
                    'id'           => 'project-' . $project->id,
                    'source_id'    => $project->id,
                    'source_type'  => 'proyecto',
                    'project_id'   => $project->id,
                    'project_name' => $project->nombre,
                    'title'        => $project->nombre,
                    'description'  => $project->descripcion,
                    'type'         => 'proyecto',
                    'status'       => $this->normalizeStatus($project->estado),
                    'date'         => $projStart?->toDateString(),
                    'start_at'     => $projStart?->startOfDay()->toDateTimeString(),
                    'end_at'       => $projEnd?->endOfDay()->toDateTimeString(),
                    'all_day'      => true,
                ]);
            });
    }

    private function formatManualEvent(CalendarEvent $event): array
    {
        $date = $event->start_at?->toDateString();

        return $this->baseEventPayload([
            'id'           => $event->id,
            'source_id'    => $event->source_id ?? $event->id,
            'source_type'  => $event->source_type ?? 'evento',
            'project_id'   => $event->project_id,
            'project_name' => $event->project?->nombre,
            'title'        => $event->title,
            'description'  => $event->description,
            'type'         => $event->type ?? 'evento',
            'status'       => $event->status ?? 'pendiente',
            'date'         => $date,
            'start_at'     => $event->start_at?->toDateTimeString(),
            'end_at'       => $event->end_at?->toDateTimeString(),
            'all_day'      => (bool) $event->all_day,
            'created_at'   => $event->created_at?->toDateTimeString(),
            'updated_at'   => $event->updated_at?->toDateTimeString(),
        ]);
    }

    private function baseEventPayload(array $event): array
    {
        return array_merge($event, [
            // Android-friendly aliases
            'start'           => $event['start_at'] ?? null,
            'end'             => $event['end_at'] ?? null,
            'source'          => $event['source_type'] ?? null,
            // Spanish aliases for web
            'titulo'          => $event['title'] ?? null,
            'descripcion'     => $event['description'] ?? null,
            'tipo'            => $event['type'] ?? null,
            'estado'          => $event['status'] ?? null,
            'fecha'           => $event['date'] ?? null,
            'proyecto_id'     => $event['project_id'] ?? null,
            'proyecto_nombre' => $event['project_name'] ?? null,
        ]);
    }

    private function findProjectForUser(int $projectId): Proyecto|JsonResponse
    {
        $project = Proyecto::find($projectId);

        if (! $project) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if (! $project->isAccessibleBy(Auth::id())) {
            return response()->json(['message' => 'No tienes permiso para acceder a este proyecto.'], 403);
        }

        return $project;
    }

    private function canAccessEvent(CalendarEvent $event): bool
    {
        if ($event->project_id) {
            return Proyecto::accessibleBy(Auth::id())->where('id', $event->project_id)->exists();
        }

        return $event->user_id === Auth::id();
    }

    private function validateCalendarFilters(Request $request): array|JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'start'      => ['nullable', 'date'],
            'end'        => ['nullable', 'date', 'after_or_equal:start'],
            'project_id' => ['nullable', 'integer'],
            'type'       => ['nullable', 'string', Rule::in(self::TYPES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los filtros del calendario no son validos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }

    private function validateEventPayload(Request $request, bool $partial = false): array|JsonResponse
    {
        $payload = $this->normalizedEventPayload($request);

        $validator = Validator::make($payload, [
            'title'       => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type'        => ['sometimes', 'string', Rule::in(self::TYPES)],
            'status'      => ['sometimes', 'string', Rule::in(self::STATUSES)],
            'start_at'    => ['sometimes', 'nullable', 'date'],
            'end_at'      => ['sometimes', 'nullable', 'date'],
            'all_day'     => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos del evento no son validos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (!empty($validated['start_at']) && !empty($validated['end_at'])) {
            $startAt = Carbon::parse($validated['start_at']);
            $endAt = Carbon::parse($validated['end_at']);

            if ($endAt->lt($startAt)) {
                return response()->json([
                    'message' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
                    'errors'  => ['end_at' => ['La fecha de fin no puede ser anterior a la fecha de inicio.']],
                ], 422);
            }
        }

        return $validated;
    }

    private function normalizedEventPayload(Request $request): array
    {
        $payload = $request->all();

        $aliases = [
            'titulo'       => 'title',
            'descripcion'  => 'description',
            'tipo'         => 'type',
            'estado'       => 'status',
            'fecha_inicio' => 'start_at',
            'fecha_fin'    => 'end_at',
        ];

        foreach ($aliases as $alias => $field) {
            if (!array_key_exists($field, $payload) && array_key_exists($alias, $payload)) {
                $payload[$field] = $payload[$alias];
            }
        }

        return array_intersect_key($payload, array_flip([
            'title',
            'description',
            'type',
            'status',
            'start_at',
            'end_at',
            'all_day',
        ]));
    }

    private function dateInRange($date, ?Carbon $start, ?Carbon $end): bool
    {
        if (! $date) {
            return $start === null && $end === null;
        }

        $date = Carbon::parse($date)->startOfDay();

        if ($start && $date->lt($start->copy()->startOfDay())) {
            return false;
        }

        if ($end && $date->gt($end->copy()->endOfDay())) {
            return false;
        }

        return true;
    }

    private function eventOverlapsRange($startAt, $endAt, ?Carbon $rangeStart, ?Carbon $rangeEnd): bool
    {
        if (! $startAt) {
            return $rangeStart === null && $rangeEnd === null;
        }

        $eventStart = Carbon::parse($startAt);
        $eventEnd = $endAt ? Carbon::parse($endAt) : $eventStart->copy();

        if ($rangeStart && $eventEnd->lt($rangeStart)) {
            return false;
        }

        if ($rangeEnd && $eventStart->gt($rangeEnd)) {
            return false;
        }

        return true;
    }

    private function normalizeStatus(?string $status): string
    {
        $value = str($status ?? '')->lower()->ascii()->replace([' ', '-'], '_')->toString();

        return match ($value) {
            'completado', 'completada', 'done', 'finalizado', 'finalizada' => 'completado',
            'cancelado', 'cancelada' => 'cancelado',
            'en_progreso', 'activo', 'activa', 'en_curso' => 'en_progreso',
            default => 'pendiente',
        };
    }
}
