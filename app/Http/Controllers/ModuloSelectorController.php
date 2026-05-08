<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Support\Facades\Auth;

class ModuloSelectorController extends Controller
{
    private array $modulos = [
        'sprints' => [
            'label'     => 'Sprints',
            'desc'      => 'Planifica y gestiona los sprints del proyecto.',
            'ruta'      => 'proyectos.sprints.index',
            'conteo'    => 'sprints_count',
            'conteo_label' => 'sprint',
        ],
        'tareas' => [
            'label'     => 'Tareas',
            'desc'      => 'Gestiona el tablero de tareas del proyecto.',
            'ruta'      => 'proyectos.tasks.index',
            'conteo'    => 'tasks_count',
            'conteo_label' => 'tarea',
        ],
        'requerimientos' => [
            'label'     => 'Requerimientos',
            'desc'      => 'Administra los requerimientos e historias de usuario.',
            'ruta'      => 'proyectos.requirements.index',
            'conteo'    => 'requirements_count',
            'conteo_label' => 'requerimiento',
        ],
        'insumos' => [
            'label'     => 'Insumos',
            'desc'      => 'Registra y consulta los insumos del proyecto.',
            'ruta'      => 'proyectos.inputs.index',
            'conteo'    => 'inputs_count',
            'conteo_label' => 'insumo',
        ],
    ];

    public function show(string $modulo)
    {
        abort_if(!array_key_exists($modulo, $this->modulos), 404);

        $info = $this->modulos[$modulo];

        $proyectos = Proyecto::accessibleBy(Auth::id())
            ->withCount([
                'sprints',
                'tasks',
                'requirements',
                'inputs',
                'tasks as completed_tasks_count' => fn ($q) => $q->whereHas(
                    'status', fn ($s) => $s->where('nombre', 'Completado')
                ),
            ])
            ->latest()
            ->get();

        return view('modulo.selector', compact('modulo', 'info', 'proyectos'));
    }
}
