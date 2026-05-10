<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\UserStory;
use App\Services\ProjectActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyTasksController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();

        $sprintFiltro = $request->query('sprint', 'todos');
        $estadoFiltro = $request->query('estado', 'todos');

        $tasksQuery = $this->visibleTasksQuery($userId)
            ->with(['proyecto', 'status', 'sprint', 'assignedTo']);

        if ($sprintFiltro === 'sin_sprint') {
            $tasksQuery->whereNull('sprint_id');
        } elseif ($sprintFiltro !== 'todos' && is_numeric($sprintFiltro)) {
            $tasksQuery->where('sprint_id', (int) $sprintFiltro);
        }

        if ($estadoFiltro !== 'todos' && is_numeric($estadoFiltro)) {
            $tasksQuery->where('task_status_id', (int) $estadoFiltro);
        }

        $tasks = $tasksQuery
            ->orderByRaw('CASE WHEN fecha_limite IS NULL THEN 1 ELSE 0 END')
            ->orderBy('fecha_limite')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $sprintsDisponibles = Sprint::where(function (Builder $query) use ($userId) {
                $query->whereHas('proyecto', fn (Builder $project) => $project->accessibleBy($userId))
                    ->orWhereHas('tasks', fn (Builder $task) => $task->where('assigned_to', $userId));
            })
            ->orderBy('nombre')
            ->get();

        $estadosDisponibles = TaskStatus::orderBy('orden')->get();

        return view('mis-tareas.index', compact(
            'tasks',
            'sprintFiltro',
            'sprintsDisponibles',
            'estadoFiltro',
            'estadosDisponibles'
        ));
    }

    public function create(): View
    {
        $projects = $this->accessibleProjects();
        $task = new Task([
            'assigned_to' => Auth::id(),
        ]);

        return view('mis-tareas.create', $this->formData($task, $projects, true));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateFullTask($request);
        $project = Proyecto::findOrFail($validated['proyecto_id']);

        $validated['assigned_to'] = $validated['assigned_to'] ?? Auth::id();

        $task = $project->tasks()->create($validated);

        ProjectActivityLogger::log(
            $project,
            'created',
            'tareas',
            "Tarea creada desde Mis Tareas: \"{$task->titulo}\"",
            null,
            $task
        );

        return redirect()
            ->route('mis-tareas.show', $task)
            ->with('success', 'Tarea creada correctamente.');
    }

    public function show(Task $tarea): View
    {
        $task = $this->loadVisibleTask($tarea);
        $canEdit = $this->canUpdateTask($task, Auth::id());
        $canDelete = $this->canDeleteTask($task, Auth::id());

        return view('mis-tareas.show', compact('task', 'canEdit', 'canDelete'));
    }

    public function edit(Task $tarea): View
    {
        $task = $this->loadVisibleTask($tarea);
        abort_if(!$this->canUpdateTask($task, Auth::id()), 403);

        $projects = $this->accessibleProjects();
        $canManageProjectTask = $this->canManageProjectTask($task, Auth::id());

        return view('mis-tareas.edit', $this->formData($task, $projects, $canManageProjectTask));
    }

    public function update(Request $request, Task $tarea): RedirectResponse
    {
        $task = $this->loadVisibleTask($tarea);
        abort_if(!$this->canUpdateTask($task, Auth::id()), 403);

        $oldValues = $task->only([
            'titulo',
            'descripcion',
            'proyecto_id',
            'task_status_id',
            'user_story_id',
            'sprint_id',
            'assigned_to',
            'fecha_limite',
        ]);

        if ($this->canManageProjectTask($task, Auth::id())) {
            $validated = $this->validateFullTask($request);
            $project = Proyecto::findOrFail($validated['proyecto_id']);
            $task->update($validated);
        } else {
            $validated = $request->validate([
                'task_status_id' => ['required', 'integer', 'exists:task_statuses,id'],
            ], $this->messages());

            $project = $task->proyecto;
            $task->update($validated);
        }

        $task->refresh()->load('proyecto');
        $newValues = $task->only([
            'titulo',
            'descripcion',
            'proyecto_id',
            'task_status_id',
            'user_story_id',
            'sprint_id',
            'assigned_to',
            'fecha_limite',
        ]);

        ProjectActivityLogger::log(
            $project,
            'updated',
            'tareas',
            "Tarea editada desde Mis Tareas: \"{$task->titulo}\"",
            null,
            $task,
            $oldValues,
            $newValues
        );

        return redirect()
            ->route('mis-tareas.show', $task)
            ->with('success', 'Tarea actualizada correctamente.');
    }

    public function destroy(Task $tarea): RedirectResponse
    {
        $task = $this->loadVisibleTask($tarea);
        abort_if(!$this->canDeleteTask($task, Auth::id()), 403);

        $project = $task->proyecto;
        $title = $task->titulo;

        if ($project) {
            ProjectActivityLogger::log(
                $project,
                'deleted',
                'tareas',
                "Tarea eliminada desde Mis Tareas: \"{$title}\"",
                null,
                $task
            );
        }

        $task->delete();

        return redirect()
            ->route('mis-tareas.index')
            ->with('success', 'Tarea eliminada correctamente.');
    }

    private function visibleTasksQuery(int $userId): Builder
    {
        return Task::query()
            ->where(function (Builder $query) use ($userId) {
                $query->where('assigned_to', $userId)
                    ->orWhereHas('proyecto', fn (Builder $project) => $project->accessibleBy($userId));
            });
    }

    private function loadVisibleTask(Task $task): Task
    {
        $task->loadMissing(['proyecto.members', 'status', 'userStory.requirement', 'sprint', 'assignedTo']);

        abort_if(!$this->canViewTask($task, Auth::id()), 403);

        return $task;
    }

    private function canViewTask(Task $task, int $userId): bool
    {
        return (int) $task->assigned_to === $userId
            || $this->canManageProjectTask($task, $userId);
    }

    private function canUpdateTask(Task $task, int $userId): bool
    {
        return $this->canViewTask($task, $userId);
    }

    private function canDeleteTask(Task $task, int $userId): bool
    {
        return $this->canViewTask($task, $userId);
    }

    private function canManageProjectTask(Task $task, int $userId): bool
    {
        return $task->proyecto?->isAccessibleBy($userId) ?? false;
    }

    private function formData(Task $task, Collection $projects, bool $canManageProjectTask): array
    {
        $projectIds = $projects->pluck('id');

        $statuses = TaskStatus::orderBy('orden')->get();
        $sprints = Sprint::whereIn('proyecto_id', $projectIds)
            ->orderBy('nombre')
            ->get();
        $userStories = UserStory::whereHas(
                'requirement',
                fn (Builder $query) => $query->whereIn('proyecto_id', $projectIds)
            )
            ->orderBy('titulo')
            ->get();
        $assignableUsers = $this->assignableUsersForProjects($projects);
        $assignableUserProjects = $this->assignableUserProjects($projects);

        return compact(
            'task',
            'projects',
            'statuses',
            'sprints',
            'userStories',
            'assignableUsers',
            'assignableUserProjects',
            'canManageProjectTask'
        );
    }

    private function accessibleProjects(): Collection
    {
        return Proyecto::accessibleBy(Auth::id())
            ->with([
                'user',
                'members' => fn ($query) => $query->where('status', 'activo')
                    ->whereNotNull('user_id')
                    ->with('user'),
            ])
            ->orderBy('nombre')
            ->get();
    }

    private function validateFullTask(Request $request): array
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'proyecto_id' => ['required', 'integer', 'exists:proyectos,id'],
            'task_status_id' => ['required', 'integer', 'exists:task_statuses,id'],
            'user_story_id' => ['nullable', 'integer', 'exists:user_stories,id'],
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'fecha_limite' => ['nullable', 'date'],
        ], $this->messages());

        $project = Proyecto::accessibleBy(Auth::id())->find($validated['proyecto_id']);

        if (!$project) {
            throw ValidationException::withMessages([
                'proyecto_id' => 'No tienes acceso al proyecto seleccionado.',
            ]);
        }

        if (!empty($validated['sprint_id'])
            && !$project->sprints()->whereKey($validated['sprint_id'])->exists()) {
            throw ValidationException::withMessages([
                'sprint_id' => 'El sprint seleccionado no pertenece al proyecto.',
            ]);
        }

        if (!empty($validated['user_story_id'])
            && !UserStory::whereKey($validated['user_story_id'])
                ->whereHas('requirement', fn (Builder $query) => $query->where('proyecto_id', $project->id))
                ->exists()) {
            throw ValidationException::withMessages([
                'user_story_id' => 'La historia de usuario seleccionada no pertenece al proyecto.',
            ]);
        }

        if (!empty($validated['assigned_to'])
            && !$this->isAssignableUserForProject($project, (int) $validated['assigned_to'])) {
            throw ValidationException::withMessages([
                'assigned_to' => 'El responsable seleccionado no es miembro activo del proyecto.',
            ]);
        }

        if (!empty($validated['fecha_limite']) && !empty($validated['sprint_id'])) {
            $sprint = $project->sprints()->find($validated['sprint_id']);
            if ($sprint?->fecha_inicio && $validated['fecha_limite'] < $sprint->fecha_inicio->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'fecha_limite' => 'La fecha limite no puede ser anterior al inicio del sprint.',
                ]);
            }
            if ($sprint?->fecha_fin && $validated['fecha_limite'] > $sprint->fecha_fin->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'fecha_limite' => 'La fecha limite no puede ser posterior al fin del sprint.',
                ]);
            }
        }

        return $validated;
    }

    private function isAssignableUserForProject(Proyecto $project, int $userId): bool
    {
        if ((int) $project->user_id === $userId) {
            return true;
        }

        return $project->members()
            ->where('status', 'activo')
            ->where('user_id', $userId)
            ->exists();
    }

    private function assignableUsersForProjects(Collection $projects): Collection
    {
        $users = collect();

        foreach ($projects as $project) {
            if ($project->user) {
                $users->push($project->user);
            }

            foreach ($project->members as $member) {
                if ($member->user) {
                    $users->push($member->user);
                }
            }
        }

        if ($users->doesntContain('id', Auth::id())) {
            $currentUser = User::find(Auth::id());
            if ($currentUser) {
                $users->push($currentUser);
            }
        }

        return $users
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    private function assignableUserProjects(Collection $projects): array
    {
        $map = [];

        foreach ($projects as $project) {
            if ($project->user) {
                $map[$project->user->id][] = $project->id;
            }

            foreach ($project->members as $member) {
                if ($member->user) {
                    $map[$member->user->id][] = $project->id;
                }
            }
        }

        return array_map(
            fn (array $ids) => collect($ids)->unique()->values()->all(),
            $map
        );
    }

    private function messages(): array
    {
        return [
            'titulo.required' => 'El titulo es obligatorio.',
            'titulo.max' => 'El titulo no puede superar los 255 caracteres.',
            'proyecto_id.required' => 'El proyecto es obligatorio.',
            'proyecto_id.exists' => 'El proyecto seleccionado no existe.',
            'task_status_id.required' => 'El estado es obligatorio.',
            'task_status_id.exists' => 'El estado seleccionado no es valido.',
            'user_story_id.exists' => 'La historia de usuario seleccionada no existe.',
            'sprint_id.exists' => 'El sprint seleccionado no existe.',
            'assigned_to.exists' => 'El responsable seleccionado no existe.',
            'fecha_limite.date' => 'La fecha limite no tiene un formato valido.',
        ];
    }
}
