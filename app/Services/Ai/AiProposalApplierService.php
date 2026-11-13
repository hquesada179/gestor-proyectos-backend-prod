<?php

namespace App\Services\Ai;

use App\Models\AiProjectChat;
use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\AiResponseParserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Servicio encargado de la persistencia transaccional de propuestas de IA en entidades Scrum.
 */
class AiProposalApplierService
{
    public function __construct(
        private readonly AiResponseParserService $parser,
    ) {}

    /**
     * Aplica la propuesta de creación persistiendo un nuevo proyecto con sus requerimientos, tareas, sprints e insumos.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function applyCreate(array $data, int $userId): array
    {
        // Validamos la consistencia mínima del borrador antes de abrir la transacción
        $validationError = $this->parser->validateCreateDraft($data);
        if ($validationError) {
            throw new InvalidArgumentException($validationError);
        }

        try {
            DB::beginTransaction();

            // Creación de la entidad principal del proyecto
            $proyecto = Proyecto::create([
                'user_id' => $userId,
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'estado' => 'activo',
                'fecha_inicio' => now(),
            ]);

            $counts = ['requerimientos' => 0, 'tareas' => 0, 'sprints' => 0, 'insumos' => 0];

            // Requerimientos iniciales con numeración consecutiva
            foreach ($data['requerimientos'] ?? [] as $i => $req) {
                if (empty($req['titulo'])) {
                    continue;
                }
                $tipo = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $proyecto->requirements()->create([
                    'codigo' => ($tipo === 'no_funcional' ? 'RNF-' : 'RF-').str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'titulo' => $req['titulo'],
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo' => $tipo,
                    'prioridad' => in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media',
                ]);
                $counts['requerimientos']++;
            }

            // Obtenemos o creamos el estado inicial por defecto para las tareas
            $status = TaskStatus::orderBy('orden')->first()
                ?? TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#6B7280', 'orden' => 1]);

            // Tareas iniciales del backlog
            foreach ($data['tareas'] ?? [] as $task) {
                if (empty($task['titulo'])) {
                    continue;
                }
                $proyecto->tasks()->create([
                    'titulo' => $task['titulo'],
                    'descripcion' => $task['descripcion'] ?? '',
                    'task_status_id' => $status->id,
                ]);
                $counts['tareas']++;
            }

            // Sprints iniciales estimados
            foreach ($data['sprints'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) {
                    continue;
                }
                $semanas = max(1, (int) ($sprint['semanas'] ?? $sprint['duracion_semanas'] ?? 2));
                $proyecto->sprints()->create([
                    'nombre' => $sprint['nombre'],
                    'objetivo' => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->addWeeks($semanas)->toDateString(),
                    'estado' => 'planificado',
                ]);
                $counts['sprints']++;
            }

            // Insumos y recursos del proyecto
            foreach ($data['insumos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) {
                    continue;
                }
                $proyecto->inputs()->create([
                    'tipo' => $insumo['tipo'] ?? 'otro',
                    'titulo' => $titulo,
                    'contenido' => $insumo['contenido'] ?? $insumo['descripcion'] ?? '',
                ]);
                $counts['insumos']++;
            }

            DB::commit();

            Log::info('AiProposalApplierService@applyCreate: project created', [
                'proyecto_id' => $proyecto->id,
                'counts' => $counts,
            ]);

            return [
                'proyecto' => $proyecto,
                'counts' => $counts,
                'message' => "Proyecto «{$proyecto->nombre}» creado con éxito.",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AiProposalApplierService@applyCreate: error', ['message' => $e->getMessage()]);
            throw new RuntimeException('Error al guardar: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Aplica sugerencias de mejora sobre un proyecto existente.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function applyImprove(array $data, int $userId): array
    {
        $proyectoId = $data['_proyecto_id'] ?? null;

        // Se valida que la propuesta indique a qué proyecto debe asociarse
        if (! $proyectoId) {
            throw new InvalidArgumentException('No se encontró el proyecto en la propuesta.');
        }

        // Verificamos acceso al proyecto con la barrera canónica de autorización del sistema
        $proyecto = Proyecto::accessibleBy($userId)->where('id', $proyectoId)->first();
        if (! $proyecto) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permiso.');
        }

        try {
            DB::beginTransaction();

            $counts = ['reqs_new' => 0, 'tasks_new' => 0, 'tasks_updated' => 0, 'sprints' => 0, 'insumos' => 0];
            $warnings = [];

            // Nuevos requerimientos sugeridos
            foreach ($data['requerimientos_nuevos'] ?? [] as $req) {
                if (empty($req['titulo'])) {
                    continue;
                }
                $tipo = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $proyecto->requirements()->create([
                    'titulo' => $req['titulo'],
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo' => $tipo,
                    'prioridad' => in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media',
                ]);
                $counts['reqs_new']++;
            }

            // Nuevas tareas sugeridas
            $status = TaskStatus::orderBy('orden')->first()
                ?? TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#6B7280', 'orden' => 1]);

            foreach ($data['tareas_nuevas'] ?? [] as $task) {
                if (empty($task['titulo'])) {
                    continue;
                }
                $proyecto->tasks()->create([
                    'titulo' => $task['titulo'],
                    'descripcion' => $task['descripcion'] ?? '',
                    'task_status_id' => $status->id,
                ]);
                $counts['tasks_new']++;
            }

            // Actualizaciones sobre tareas existentes
            $projectTasks = $proyecto->tasks()->get(['id', 'titulo', 'descripcion']);
            $taskIds = $projectTasks->pluck('id')->map(fn ($id) => (int) $id)->toArray();

            foreach ($data['actualizaciones_tareas'] ?? [] as $upd) {
                $taskId = (int) ($upd['id'] ?? 0);

                // Si no se proporcionó id válido, buscamos coincidencia por título
                if (! in_array($taskId, $taskIds, true)) {
                    $needle = mb_strtolower(trim($upd['titulo_actual'] ?? ''));
                    $matched = $projectTasks->first(fn ($t) => mb_strtolower(trim($t->titulo)) === $needle);
                    $taskId = $matched?->id ?? 0;
                }

                // Si no se localizó la tarea en el proyecto, registramos aviso y continuamos
                if (! $taskId || ! in_array($taskId, $taskIds, true)) {
                    $warnings[] = "Tarea «{$upd['titulo_actual']}»: no encontrada en el proyecto.";

                    continue;
                }

                Task::where('id', $taskId)->where('proyecto_id', $proyecto->id)->update([
                    'titulo' => $upd['titulo_nuevo'] ?? Task::find($taskId)->titulo,
                    'descripcion' => $upd['descripcion_nueva'] ?? Task::find($taskId)->descripcion,
                ]);
                $counts['tasks_updated']++;
            }

            // Nuevos sprints
            foreach ($data['sprints_nuevos'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) {
                    continue;
                }
                $semanas = max(1, (int) ($sprint['semanas'] ?? 2));
                $proyecto->sprints()->create([
                    'nombre' => $sprint['nombre'],
                    'objetivo' => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->addWeeks($semanas)->toDateString(),
                    'estado' => 'planificado',
                ]);
                $counts['sprints']++;
            }

            // Nuevos insumos
            foreach ($data['insumos_nuevos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) {
                    continue;
                }
                $proyecto->inputs()->create([
                    'tipo' => $insumo['tipo'] ?? 'otro',
                    'titulo' => $titulo,
                    'contenido' => $insumo['contenido'] ?? '',
                ]);
                $counts['insumos']++;
            }

            DB::commit();

            return [
                'proyecto' => $proyecto,
                'counts' => $counts,
                'warnings' => $warnings,
                'message' => "Cambios aplicados al proyecto «{$proyecto->nombre}».",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AiProposalApplierService@applyImprove: error', ['message' => $e->getMessage()]);
            throw new RuntimeException('Error al guardar: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Aplica edición completa de proyecto (actualización de existentes e inserción de nuevos).
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function applyProjectEdit(array $data, int $userId): array
    {
        $proyectoId = $data['_proyecto_id'] ?? null;

        // Se requiere identificador de proyecto
        if (! $proyectoId) {
            throw new InvalidArgumentException('No se encontro el proyecto en la propuesta.');
        }

        // Validación de pertenencia o acceso activo al proyecto
        $proyecto = Proyecto::accessibleBy($userId)->where('id', $proyectoId)->first();
        if (! $proyecto) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permiso.');
        }

        try {
            DB::beginTransaction();

            $counts = [
                'reqs_new' => 0, 'reqs_updated' => 0, 'tasks_new' => 0, 'tasks_updated' => 0,
                'sprints_new' => 0, 'sprints_updated' => 0, 'insumos_new' => 0, 'insumos_updated' => 0,
            ];

            // Actualización de nombre y descripción
            $proyecto->update([
                'nombre' => $data['nombre'] ?? $proyecto->nombre,
                'descripcion' => $data['descripcion'] ?? $proyecto->descripcion,
            ]);

            // Sincronización de requerimientos (actualizar existentes o crear nuevos)
            foreach ($data['requerimientos'] ?? [] as $req) {
                if (empty($req['titulo'])) {
                    continue;
                }
                $tipo = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $prio = in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media';

                if (! empty($req['_id'])) {
                    $existing = $proyecto->requirements()->find($req['_id']);
                    if ($existing) {
                        $existing->update([
                            'titulo' => $req['titulo'],
                            'descripcion' => $req['descripcion'] ?? '',
                            'tipo' => $tipo,
                            'prioridad' => $prio,
                        ]);
                        $counts['reqs_updated']++;

                        continue;
                    }
                }
                $proyecto->requirements()->create([
                    'titulo' => $req['titulo'],
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo' => $tipo,
                    'prioridad' => $prio,
                ]);
                $counts['reqs_new']++;
            }

            // Sincronización de tareas
            $status = TaskStatus::orderBy('orden')->first()
                ?? TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#6B7280', 'orden' => 1]);

            foreach ($data['tareas'] ?? [] as $task) {
                if (empty($task['titulo'])) {
                    continue;
                }

                if (! empty($task['_id'])) {
                    $existing = $proyecto->tasks()->find($task['_id']);
                    if ($existing) {
                        $existing->update([
                            'titulo' => $task['titulo'],
                            'descripcion' => $task['descripcion'] ?? '',
                        ]);
                        $counts['tasks_updated']++;

                        continue;
                    }
                }
                $proyecto->tasks()->create([
                    'titulo' => $task['titulo'],
                    'descripcion' => $task['descripcion'] ?? '',
                    'task_status_id' => $status->id,
                ]);
                $counts['tasks_new']++;
            }

            // Sincronización de sprints
            foreach ($data['sprints'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) {
                    continue;
                }
                $semanas = max(1, (int) ($sprint['semanas'] ?? 2));

                if (! empty($sprint['_id'])) {
                    $existing = $proyecto->sprints()->find($sprint['_id']);
                    if ($existing) {
                        $existing->update([
                            'nombre' => $sprint['nombre'],
                            'objetivo' => $sprint['objetivo'] ?? '',
                        ]);
                        $counts['sprints_updated']++;

                        continue;
                    }
                }
                $proyecto->sprints()->create([
                    'nombre' => $sprint['nombre'],
                    'objetivo' => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->addWeeks($semanas)->toDateString(),
                    'estado' => 'planificado',
                ]);
                $counts['sprints_new']++;
            }

            // Sincronización de insumos
            foreach ($data['insumos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) {
                    continue;
                }

                if (! empty($insumo['_id'])) {
                    $existing = $proyecto->inputs()->find($insumo['_id']);
                    if ($existing) {
                        $existing->update([
                            'titulo' => $titulo,
                            'tipo' => $insumo['tipo'] ?? 'otro',
                            'contenido' => $insumo['contenido'] ?? '',
                        ]);
                        $counts['insumos_updated']++;

                        continue;
                    }
                }
                $proyecto->inputs()->create([
                    'tipo' => $insumo['tipo'] ?? 'otro',
                    'titulo' => $titulo,
                    'contenido' => $insumo['contenido'] ?? '',
                ]);
                $counts['insumos_new']++;
            }

            DB::commit();

            Log::info('AiProposalApplierService@applyProjectEdit: applied', [
                'proyecto_id' => $proyecto->id,
                'counts' => $counts,
            ]);

            return [
                'proyecto' => $proyecto,
                'counts' => $counts,
                'message' => "Cambios aplicados al proyecto «{$proyecto->nombre}».",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AiProposalApplierService@applyProjectEdit: error', ['message' => $e->getMessage()]);
            throw new RuntimeException('Error al guardar: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Carga y estructura los datos de un proyecto existente para su edición mediante el asistente.
     *
     * @throws InvalidArgumentException
     */
    public function loadProjectStructure(int $projectId, int $userId): array
    {
        // Se valida el acceso al proyecto empleando la barrera canónica de autorización del sistema
        $proyecto = Proyecto::accessibleBy($userId)->where('id', $projectId)->first();

        if (! $proyecto) {
            throw new InvalidArgumentException('Proyecto no encontrado.');
        }

        // Mapeamos requerimientos a la estructura esperada por el borrador interactivo
        $requerimientos = $proyecto->requirements()
            ->get(['id', 'titulo', 'descripcion', 'tipo', 'prioridad'])
            ->map(fn ($r) => [
                '_id' => $r->id,
                'titulo' => $r->titulo,
                'descripcion' => $r->descripcion ?? '',
                'tipo' => $r->tipo ?? 'funcional',
                'prioridad' => $r->prioridad ?? 'media',
            ])->toArray();

        // Mapeamos tareas con su estado correspondiente
        $tareas = $proyecto->tasks()
            ->with('status:id,nombre')
            ->get(['id', 'titulo', 'descripcion', 'task_status_id'])
            ->map(fn ($t) => [
                '_id' => $t->id,
                'titulo' => $t->titulo,
                'descripcion' => $t->descripcion ?? '',
                'prioridad' => 'media',
                'estado' => $t->status?->nombre ?? 'pendiente',
            ])->toArray();

        // Mapeamos sprints calculando la duración estimada en semanas
        $sprints = $proyecto->sprints()
            ->get(['id', 'nombre', 'objetivo', 'fecha_inicio', 'fecha_fin'])
            ->map(function ($s) {
                $semanas = 2;
                if ($s->fecha_inicio && $s->fecha_fin) {
                    $semanas = max(1, (int) ceil($s->fecha_inicio->diffInDays($s->fecha_fin) / 7));
                }

                return ['_id' => $s->id, 'nombre' => $s->nombre, 'objetivo' => $s->objetivo ?? '', 'semanas' => $semanas];
            })->toArray();

        // Mapeamos los insumos asociados al proyecto
        $insumos = $proyecto->inputs()
            ->get(['id', 'titulo', 'tipo', 'contenido'])
            ->map(fn ($i) => [
                '_id' => $i->id,
                'titulo' => $i->titulo,
                'tipo' => $i->tipo ?? 'otro',
                'contenido' => $i->contenido ?? '',
            ])->toArray();

        $data = [
            '_tipo' => 'project_edit',
            '_proyecto_id' => $proyecto->id,
            'nombre' => $proyecto->nombre,
            'descripcion' => $proyecto->descripcion ?? '',
            'requerimientos' => $requerimientos,
            'tareas' => $tareas,
            'sprints' => $sprints,
            'insumos' => $insumos,
        ];

        // Creamos un registro borrador de chat para habilitar el flujo de confirmación/descarte
        $record = AiProjectChat::create([
            'user_id' => $userId,
            'proyecto_id' => $proyecto->id,
            'tipo_accion' => 'editar_proyecto',
            'prompt_usuario' => 'Carga de estructura del proyecto para edición directa',
            'estado' => 'borrador',
            'datos_detectados' => $data,
        ]);

        return [
            'ok' => true,
            'chat_id' => $record->id,
            'proyecto_nombre' => $proyecto->nombre,
            'counts' => [
                'requerimientos' => count($requerimientos),
                'tareas' => count($tareas),
                'sprints' => count($sprints),
                'insumos' => count($insumos),
            ],
            'data' => $data,
        ];
    }
}
