<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Proyecto $proyecto, Request $request)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $tasks    = $this->filteredQuery($proyecto, $request)->with('status', 'assignedTo')->latest()->paginate(15)->withQueryString();
        $statuses = TaskStatus::orderBy('orden')->get();
        $sprints  = $proyecto->sprints()->orderBy('created_at')->get();
        $assignees = User::whereIn('id',
            $proyecto->tasks()->whereNotNull('assigned_to')->pluck('assigned_to')
        )->orderBy('name')->get();

        return view('proyectos.tasks.index', compact('proyecto', 'tasks', 'statuses', 'sprints', 'assignees'));
    }

    public function export(Proyecto $proyecto, Request $request)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $tasks = $this->filteredQuery($proyecto, $request)
            ->with('status', 'sprint', 'userStory', 'assignedTo')
            ->latest()
            ->get();

        $filename = 'tareas-' . \Illuminate\Support\Str::slug($proyecto->nombre) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($tasks) {
            $handle = fopen('php://output', 'w');

            // BOM para compatibilidad con Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['Título', 'Descripción', 'Estado', 'Responsable', 'Fecha límite', 'Sprint', 'Historia de usuario', 'Fecha de creación']);

            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->titulo,
                    $task->descripcion ?? '',
                    $task->status->nombre ?? '',
                    $task->assignedTo->name ?? '',
                    $task->fecha_limite?->format('d/m/Y') ?? '',
                    $task->sprint->nombre ?? '',
                    $task->userStory->titulo ?? '',
                    $task->created_at->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function filteredQuery(Proyecto $proyecto, Request $request)
    {
        $query = $proyecto->tasks();

        if ($request->filled('estado')) {
            $query->where('task_status_id', $request->estado);
        }

        if ($request->filled('sprint')) {
            if ($request->sprint === 'sin_sprint') {
                $query->whereNull('sprint_id');
            } else {
                $validSprintId = $proyecto->sprints()->where('id', $request->sprint)->value('id');
                if ($validSprintId) {
                    $query->where('sprint_id', $validSprintId);
                }
            }
        }

        if ($request->filled('responsable')) {
            if ($request->responsable === 'sin_responsable') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->responsable);
            }
        }

        return $query;
    }

    public function create(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $statuses    = TaskStatus::orderBy('orden')->get();
        $userStories = $this->userStoriesForProject($proyecto);
        $sprints     = $proyecto->sprints()->orderBy('created_at')->get();
        $users       = User::orderBy('name')->get();

        return view('proyectos.tasks.create', compact('proyecto', 'statuses', 'userStories', 'sprints', 'users'));
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $validated = $request->validate(
            $this->rules($proyecto, $request),
            $this->messages()
        );

        $task = $proyecto->tasks()->create($validated);

        ProjectActivityLogger::log($proyecto, 'created', 'tareas',
            "Tarea creada: «{$task->titulo}»", null, $task
        );

        return redirect()->route('proyectos.tasks.index', $proyecto)
            ->with('success', 'Tarea creada correctamente.');
    }

    public function show(Proyecto $proyecto, Task $task)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($task->proyecto_id !== $proyecto->id, 404);

        $task->load('status', 'userStory', 'sprint', 'assignedTo');

        return view('proyectos.tasks.show', compact('proyecto', 'task'));
    }

    public function edit(Proyecto $proyecto, Task $task)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($task->proyecto_id !== $proyecto->id, 404);

        $statuses    = TaskStatus::orderBy('orden')->get();
        $userStories = $this->userStoriesForProject($proyecto);
        $sprints     = $proyecto->sprints()->orderBy('created_at')->get();
        $users       = User::orderBy('name')->get();

        return view('proyectos.tasks.edit', compact('proyecto', 'task', 'statuses', 'userStories', 'sprints', 'users'));
    }

    public function update(Request $request, Proyecto $proyecto, Task $task)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($task->proyecto_id !== $proyecto->id, 404);

        $validated = $request->validate(
            $this->rules($proyecto, $request),
            $this->messages()
        );

        $oldValues = $task->only(['titulo', 'task_status_id', 'sprint_id', 'assigned_to', 'fecha_limite']);
        $task->update($validated);
        $newValues = $task->fresh()->only(['titulo', 'task_status_id', 'sprint_id', 'assigned_to', 'fecha_limite']);

        ProjectActivityLogger::log($proyecto, 'updated', 'tareas',
            "Tarea editada: «{$task->titulo}»", null, $task, $oldValues, $newValues
        );

        return redirect()->route('proyectos.tasks.show', [$proyecto, $task])
            ->with('success', 'Tarea actualizada correctamente.');
    }

    public function destroy(Proyecto $proyecto, Task $task)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($task->proyecto_id !== $proyecto->id, 404);

        $titulo = $task->titulo;
        ProjectActivityLogger::log($proyecto, 'deleted', 'tareas',
            "Tarea eliminada: «{$titulo}»"
        );

        $task->delete();

        return redirect()->route('proyectos.tasks.index', $proyecto)
            ->with('success', 'Tarea eliminada correctamente.');
    }

    private function rules(Proyecto $proyecto, Request $request): array
    {
        $validUserStoryIds = $this->userStoriesForProject($proyecto)->pluck('id')->toArray();
        $validSprintIds    = $proyecto->sprints()->pluck('id')->toArray();

        $sprintDateRule = function ($attribute, $value, $fail) use ($request, $proyecto) {
            $sprintId = $request->input('sprint_id');

            if (!$sprintId || !$value) {
                return;
            }

            $sprint = $proyecto->sprints()->find($sprintId);

            if (!$sprint) {
                return; // ya lo rechaza Rule::in arriba
            }

            if ($sprint->fecha_inicio && $value < $sprint->fecha_inicio->format('Y-m-d')) {
                $fail(
                    'La fecha límite no puede ser anterior a la fecha de inicio del sprint ' .
                    '(' . $sprint->fecha_inicio->format('d/m/Y') . ').'
                );
                return;
            }

            if ($sprint->fecha_fin && $value > $sprint->fecha_fin->format('Y-m-d')) {
                $fail(
                    'La fecha límite no puede ser posterior a la fecha de fin del sprint ' .
                    '(' . $sprint->fecha_fin->format('d/m/Y') . ').'
                );
            }
        };

        return [
            'titulo'         => ['required', 'string', 'max:255'],
            'descripcion'    => ['nullable', 'string', 'max:5000'],
            'task_status_id' => ['required', 'exists:task_statuses,id'],
            'user_story_id'  => ['nullable', Rule::in($validUserStoryIds)],
            'sprint_id'      => ['nullable', Rule::in($validSprintIds)],
            'assigned_to'    => ['nullable', 'exists:users,id'],
            'fecha_limite'   => ['nullable', 'date', $sprintDateRule],
        ];
    }

    private function messages(): array
    {
        return [
            'titulo.required'         => 'El título es obligatorio.',
            'titulo.max'              => 'El título no puede superar los 255 caracteres.',
            'task_status_id.required' => 'El estado es obligatorio.',
            'task_status_id.exists'   => 'El estado seleccionado no es válido.',
            'user_story_id.in'        => 'La historia de usuario seleccionada no es válida.',
            'sprint_id.in'            => 'El sprint seleccionado no es válido.',
            'fecha_limite.date'       => 'La fecha límite no tiene un formato válido.',
        ];
    }

    private function userStoriesForProject(Proyecto $proyecto)
    {
        return \App\Models\UserStory::whereHas('requirement', function ($q) use ($proyecto) {
            $q->where('proyecto_id', $proyecto->id);
        })->get();
    }
}
