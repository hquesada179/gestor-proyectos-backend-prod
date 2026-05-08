<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::accessibleBy(Auth::id())
            ->withCount(['tasks', 'sprints', 'requirements'])
            ->latest()
            ->get();

        return view('proyectos.index', compact('proyectos'));
    }

    public function create()
    {
        return view('proyectos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $proyecto = Auth::user()->proyectos()->create($validated);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto creado correctamente.');
    }

    public function show(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $stats = [
            'inputs'       => $proyecto->inputs()->count(),
            'requirements' => $proyecto->requirements()->count(),
            'userStories'  => $proyecto->userStories()->count(),
            'tasks'        => $proyecto->tasks()->count(),
            'sprints'      => $proyecto->sprints()->count(),
        ];

        $tasksByStatus = $proyecto->tasks()
            ->join('task_statuses', 'tasks.task_status_id', '=', 'task_statuses.id')
            ->selectRaw('task_statuses.id as status_id, task_statuses.nombre as nombre, count(*) as total')
            ->groupBy('task_statuses.id', 'task_statuses.nombre', 'task_statuses.orden')
            ->orderBy('task_statuses.orden')
            ->get();

        $kanbanStatuses = TaskStatus::orderBy('orden')->get();
        $kanbanSprints  = $proyecto->sprints()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn($q) => $q->whereHas(
                    'status', fn($sq) => $sq->where('nombre', 'Completado')
                ),
            ])
            ->orderBy('nombre')
            ->get();
        $kanbanSprint   = request('kanban_sprint', 'todos');

        $kanbanQuery = $proyecto->tasks()->with('assignedTo');

        if ($kanbanSprint === 'sin_sprint') {
            $kanbanQuery->whereNull('sprint_id');
        } elseif ($kanbanSprint !== 'todos' && is_numeric($kanbanSprint)) {
            $kanbanQuery->where('sprint_id', (int) $kanbanSprint);
        }

        $kanbanTasks = $kanbanQuery->orderBy('created_at')->get()->groupBy('task_status_id');

        return view('proyectos.show', compact(
            'proyecto', 'stats', 'tasksByStatus',
            'kanbanStatuses', 'kanbanTasks', 'kanbanSprints', 'kanbanSprint'
        ));
    }

    public function edit(Proyecto $proyecto)
    {
        abort_if($proyecto->user_id !== Auth::id(), 403);

        return view('proyectos.edit', compact('proyecto'));
    }

    public function update(Request $request, Proyecto $proyecto)
    {
        abort_if($proyecto->user_id !== Auth::id(), 403);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $proyecto->update($validated);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Proyecto $proyecto)
    {
        abort_if($proyecto->user_id !== Auth::id(), 403);

        $proyecto->delete();

        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto eliminado correctamente.');
    }

    private function rules(): array
    {
        return [
            'nombre'             => ['required', 'string', 'max:255'],
            'descripcion'        => ['nullable', 'string', 'max:5000'],
            'estado'             => ['required', 'string', 'in:activo,pausado,completado,cancelado'],
            'fecha_inicio'       => ['nullable', 'date'],
            'fecha_fin_estimada' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    private function messages(): array
    {
        return [
            'nombre.required'               => 'El nombre del proyecto es obligatorio.',
            'nombre.max'                    => 'El nombre no puede superar los 255 caracteres.',
            'descripcion.max'               => 'La descripción no puede superar los 5000 caracteres.',
            'estado.required'               => 'El estado es obligatorio.',
            'estado.in'                     => 'El estado seleccionado no es válido.',
            'fecha_inicio.date'             => 'La fecha de inicio no tiene un formato válido.',
            'fecha_fin_estimada.date'       => 'La fecha estimada de cierre no tiene un formato válido.',
            'fecha_fin_estimada.after_or_equal' => 'La fecha estimada de cierre debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
