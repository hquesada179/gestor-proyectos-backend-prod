<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\TaskStatus;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $request->validate(
            array_merge($this->rules(), $this->imageRules()),
            $this->messages()
        );

        $data = $request->only(['nombre', 'descripcion', 'estado', 'fecha_inicio', 'fecha_fin_estimada']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')
                ->store('proyectos', 'public');
        }

        $proyecto = Auth::user()->proyectos()->create($data);

        ProjectActivityLogger::log($proyecto, 'created', 'proyectos',
            "Proyecto creado: «{$proyecto->nombre}»", null, $proyecto
        );

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

        $request->validate(
            array_merge($this->rules(), $this->imageRules()),
            $this->messages()
        );

        $oldValues = $proyecto->only(['nombre', 'estado', 'descripcion', 'fecha_inicio', 'fecha_fin_estimada']);
        $data = $request->only(['nombre', 'descripcion', 'estado', 'fecha_inicio', 'fecha_fin_estimada']);

        if ($request->hasFile('cover_image')) {
            // Replace existing image
            if ($proyecto->cover_image) {
                Storage::disk('public')->delete($proyecto->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('proyectos', 'public');
        } elseif ($request->boolean('remove_cover_image')) {
            // Explicit removal
            if ($proyecto->cover_image) {
                Storage::disk('public')->delete($proyecto->cover_image);
            }
            $data['cover_image'] = null;
        }

        $proyecto->update($data);
        $newValues = $proyecto->fresh()->only(['nombre', 'estado', 'descripcion', 'fecha_inicio', 'fecha_fin_estimada']);

        ProjectActivityLogger::log($proyecto, 'updated', 'proyectos',
            "Proyecto editado: «{$proyecto->nombre}»", null, $proyecto, $oldValues, $newValues
        );

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Proyecto $proyecto)
    {
        abort_if($proyecto->user_id !== Auth::id(), 403);

        $nombre = $proyecto->nombre;

        if ($proyecto->cover_image) {
            Storage::disk('public')->delete($proyecto->cover_image);
        }

        ProjectActivityLogger::log($proyecto, 'deleted', 'proyectos',
            "Proyecto eliminado: «{$nombre}»"
        );

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

    private function imageRules(): array
    {
        return [
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
