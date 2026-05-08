<?php

namespace App\Http\Controllers;

use App\Models\ProjectInput;
use App\Models\Proyecto;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectInputController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $inputs = $proyecto->inputs()->latest()->paginate(15)->withQueryString();

        return view('proyectos.inputs.index', compact('proyecto', 'inputs'));
    }

    public function create(Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        return view('proyectos.inputs.create', compact('proyecto'));
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $input = $proyecto->inputs()->create($validated);

        ProjectActivityLogger::log($proyecto, 'created', 'insumos',
            "Insumo creado: «{$input->titulo}»", null, $input
        );

        return redirect()->route('proyectos.inputs.index', $proyecto)
            ->with('success', 'Insumo agregado correctamente.');
    }

    public function show(Proyecto $proyecto, ProjectInput $input)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($input->proyecto_id !== $proyecto->id, 404);

        return view('proyectos.inputs.show', compact('proyecto', 'input'));
    }

    public function edit(Proyecto $proyecto, ProjectInput $input)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($input->proyecto_id !== $proyecto->id, 404);

        return view('proyectos.inputs.edit', compact('proyecto', 'input'));
    }

    public function update(Request $request, Proyecto $proyecto, ProjectInput $input)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($input->proyecto_id !== $proyecto->id, 404);

        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $oldValues = $input->only(['titulo', 'tipo']);
        $input->update($validated);
        $newValues = $input->fresh()->only(['titulo', 'tipo']);

        ProjectActivityLogger::log($proyecto, 'updated', 'insumos',
            "Insumo editado: «{$input->titulo}»", null, $input, $oldValues, $newValues
        );

        return redirect()->route('proyectos.inputs.show', [$proyecto, $input])
            ->with('success', 'Insumo actualizado correctamente.');
    }

    public function destroy(Proyecto $proyecto, ProjectInput $input)
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);
        abort_if($input->proyecto_id !== $proyecto->id, 404);

        $titulo = $input->titulo;
        ProjectActivityLogger::log($proyecto, 'deleted', 'insumos',
            "Insumo eliminado: «{$titulo}»"
        );

        $input->delete();

        return redirect()->route('proyectos.inputs.index', $proyecto)
            ->with('success', 'Insumo eliminado correctamente.');
    }

    private function rules(): array
    {
        return [
            'tipo'      => ['required', 'string', 'in:documento,entrevista,observacion,reunion,otro'],
            'titulo'    => ['required', 'string', 'max:255'],
            'contenido' => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function messages(): array
    {
        return [
            'tipo.required'    => 'El tipo de insumo es obligatorio.',
            'tipo.in'          => 'El tipo seleccionado no es válido.',
            'titulo.required'  => 'El título es obligatorio.',
            'titulo.max'       => 'El título no puede superar los 255 caracteres.',
            'contenido.max'    => 'El contenido no puede superar los 10000 caracteres.',
        ];
    }
}
