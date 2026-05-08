<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Requirement;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequirementController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        // Sprints vigentes ordenados: activo primero, luego planificados
        $sprints = $proyecto->sprints()
            ->whereIn('estado', ['en_progreso', 'planificado'])
            ->orderByRaw("CASE estado WHEN 'en_progreso' THEN 0 WHEN 'planificado' THEN 1 ELSE 2 END")
            ->orderBy('fecha_inicio')
            ->get();

        // Todos los requerimientos con sus historias y tareas (para detectar sprint)
        $requirements = $proyecto->requirements()
            ->with(['userStories.tasks'])
            ->orderByRaw("CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 WHEN 'baja' THEN 3 END")
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por sprint: un requerimiento pertenece a un sprint si alguna
        // de sus historias tiene una tarea asignada a ese sprint.
        $sprintGroups = [];
        $assignedIds  = [];

        foreach ($sprints as $sprint) {
            $items = $requirements->filter(function ($req) use ($sprint) {
                return $req->userStories
                    ->flatMap(fn($us) => $us->tasks)
                    ->contains('sprint_id', $sprint->id);
            })->values();

            $sprintGroups[] = ['sprint' => $sprint, 'items' => $items];
            $assignedIds    = array_merge($assignedIds, $items->pluck('id')->toArray());
        }

        // Backlog: requerimientos sin ninguna asociación a sprint activo/planificado
        $backlog = $requirements->whereNotIn('id', array_unique($assignedIds))->values();

        return view('proyectos.requirements.index', compact(
            'proyecto', 'requirements', 'sprints', 'sprintGroups', 'backlog'
        ));
    }

    public function create(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        return view('proyectos.requirements.create', compact('proyecto'));
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $requirement = $proyecto->requirements()->create($validated);

        ProjectActivityLogger::log($proyecto, 'created', 'requerimientos',
            "Requerimiento creado: «{$requirement->titulo}»", null, $requirement
        );

        return redirect()->route('proyectos.requirements.index', $proyecto)
            ->with('success', 'Requerimiento agregado correctamente.');
    }

    public function show(Proyecto $proyecto, Requirement $requirement)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($requirement->proyecto_id !== $proyecto->id, 404);

        return view('proyectos.requirements.show', compact('proyecto', 'requirement'));
    }

    public function edit(Proyecto $proyecto, Requirement $requirement)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($requirement->proyecto_id !== $proyecto->id, 404);

        return view('proyectos.requirements.edit', compact('proyecto', 'requirement'));
    }

    public function update(Request $request, Proyecto $proyecto, Requirement $requirement)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($requirement->proyecto_id !== $proyecto->id, 404);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $oldValues = $requirement->only(['titulo', 'tipo', 'prioridad', 'descripcion']);
        $requirement->update($validated);
        $newValues = $requirement->fresh()->only(['titulo', 'tipo', 'prioridad', 'descripcion']);

        ProjectActivityLogger::log($proyecto, 'updated', 'requerimientos',
            "Requerimiento editado: «{$requirement->titulo}»", null, $requirement, $oldValues, $newValues
        );

        return redirect()->route('proyectos.requirements.show', [$proyecto, $requirement])
            ->with('success', 'Requerimiento actualizado correctamente.');
    }

    public function destroy(Proyecto $proyecto, Requirement $requirement)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($requirement->proyecto_id !== $proyecto->id, 404);

        $titulo = $requirement->titulo;
        ProjectActivityLogger::log($proyecto, 'deleted', 'requerimientos',
            "Requerimiento eliminado: «{$titulo}»"
        );

        $requirement->delete();

        return redirect()->route('proyectos.requirements.index', $proyecto)
            ->with('success', 'Requerimiento eliminado correctamente.');
    }

    private function rules(): array
    {
        return [
            'codigo'      => ['nullable', 'string', 'max:50'],
            'titulo'      => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'tipo'        => ['required', 'string', 'in:funcional,no_funcional'],
            'prioridad'   => ['required', 'string', 'in:alta,media,baja'],
        ];
    }

    private function messages(): array
    {
        return [
            'titulo.required'      => 'El título es obligatorio.',
            'titulo.max'           => 'El título no puede superar los 255 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max'      => 'La descripción no puede superar los 5000 caracteres.',
            'codigo.max'           => 'El código no puede superar los 50 caracteres.',
            'tipo.required'        => 'El tipo es obligatorio.',
            'tipo.in'              => 'El tipo debe ser funcional o no funcional.',
            'prioridad.required'   => 'La prioridad es obligatoria.',
            'prioridad.in'         => 'La prioridad debe ser alta, media o baja.',
        ];
    }
}
