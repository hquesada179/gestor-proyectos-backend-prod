<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SprintController extends Controller
{
    public function index(Request $request, Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $query = $proyecto->sprints()->withCount('tasks')->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $sprints = $query->get();

        return view('proyectos.sprints.index', compact('proyecto', 'sprints'));
    }

    public function create(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        return view('proyectos.sprints.create', compact('proyecto'));
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $proyecto->sprints()->create($validated);

        return redirect()->route('proyectos.sprints.index', $proyecto)
            ->with('success', 'Sprint creado correctamente.');
    }

    public function show(Proyecto $proyecto, Sprint $sprint)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($sprint->proyecto_id !== $proyecto->id, 404);

        $tasks = $sprint->tasks()->with('status', 'assignedTo')->orderBy('created_at')->get();

        $total      = $tasks->count();
        $completadas = $tasks->filter(fn($t) => strtolower($t->status->nombre ?? '') === 'completado')->count();
        $progreso   = $total > 0 ? round($completadas / $total * 100) : 0;
        $porEstado  = $tasks->groupBy(fn($t) => $t->status->nombre ?? '—');

        return view('proyectos.sprints.show', compact('proyecto', 'sprint', 'tasks', 'total', 'completadas', 'progreso', 'porEstado'));
    }

    public function edit(Proyecto $proyecto, Sprint $sprint)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($sprint->proyecto_id !== $proyecto->id, 404);

        return view('proyectos.sprints.edit', compact('proyecto', 'sprint'));
    }

    public function update(Request $request, Proyecto $proyecto, Sprint $sprint)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($sprint->proyecto_id !== $proyecto->id, 404);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $sprint->update($validated);

        return redirect()->route('proyectos.sprints.show', [$proyecto, $sprint])
            ->with('success', 'Sprint actualizado correctamente.');
    }

    public function destroy(Proyecto $proyecto, Sprint $sprint)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($sprint->proyecto_id !== $proyecto->id, 404);

        $sprint->delete();

        return redirect()->route('proyectos.sprints.index', $proyecto)
            ->with('success', 'Sprint eliminado correctamente.');
    }

    private function rules(): array
    {
        return [
            'nombre'       => ['required', 'string', 'max:255'],
            'objetivo'     => ['nullable', 'string', 'max:2000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado'       => ['required', 'string', 'in:planificado,en_progreso,completado'],
        ];
    }

    private function messages(): array
    {
        return [
            'nombre.required'          => 'El nombre del sprint es obligatorio.',
            'nombre.max'               => 'El nombre no puede superar los 255 caracteres.',
            'objetivo.max'             => 'El objetivo no puede superar los 2000 caracteres.',
            'fecha_inicio.date'        => 'La fecha de inicio no tiene un formato válido.',
            'fecha_fin.date'           => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'estado.required'          => 'El estado es obligatorio.',
            'estado.in'                => 'El estado debe ser planificado, en progreso o completado.',
        ];
    }
}
