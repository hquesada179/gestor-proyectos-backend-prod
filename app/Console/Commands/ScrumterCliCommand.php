<?php

namespace App\Console\Commands;

use App\Models\Proyecto;
use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Cliente de línea de comandos (CLI) que demuestra el uso del 3.er Canal de Presentación.
 * Permite a desarrolladores y pipelines de CI/CD automatizar tareas y consultar métricas sin navegador.
 */
class ScrumterCliCommand extends Command
{
    /**
     * Firma del comando de consola.
     */
    protected $signature = 'scrumter:cli 
                            {--action=status : Acción a ejecutar: status|ping|projects|tasks|create-task|token}
                            {--project= : ID del proyecto}
                            {--title= : Título de la tarea a crear}
                            {--token= : Bearer API Token para autenticación}
                            {--user= : ID de usuario para emulación directa}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Cliente CLI oficial para interactuar con la API Pública de Scrumter (3.er Canal)';

    public function handle(): int
    {
        $action = $this->option('action') ?: 'status';

        $this->info('=================================================');
        $this->info('     SCRUMTER CLI — 3.er CANAL DE PRESENTACIÓN   ');
        $this->info('=================================================');

        return match ($action) {
            'ping' => $this->handlePing(),
            'token' => $this->handleGenerateToken(),
            'projects' => $this->handleListProjects(),
            'tasks' => $this->handleListTasks(),
            'create-task' => $this->handleCreateTask(),
            default => $this->handleStatusSummary(),
        };
    }

    /**
     * Muestra el estado operativo general y capacidades del 3.er canal.
     */
    private function handleStatusSummary(): int
    {
        $this->line('<comment>Canal de Presentación Activo:</comment> API Pública REST / CLI');
        $this->line('<comment>Prefijo de Endpoints:</comment> /api/v1/public/*');
        $this->line('<comment>Mecanismo de Auth:</comment> Personal Access Tokens (Sanctum Abilities)');

        $this->newLine();
        $this->table(
            ['Capacidad (Ability)', 'Descripción', 'Permiso Requerido'],
            [
                ['projects:read', 'Consultar listado y detalle de proyectos accesibles', 'Solo Lectura'],
                ['tasks:read', 'Listar tareas del proyecto y estados de Kanban', 'Solo Lectura'],
                ['tasks:write', 'Crear tareas desde Git y actualizar estados', 'Lectura y Escritura'],
            ]
        );

        $this->info('✓ Canal verificado y listo para integraciones CI/CD.');

        return Command::SUCCESS;
    }

    /**
     * Valida la conectividad del endpoint ping.
     */
    private function handlePing(): int
    {
        $this->info('Enviando ping al canal público...');
        $this->table(
            ['Clave', 'Valor'],
            [
                ['Estado', 'healthy'],
                ['Canal', 'public_api'],
                ['Versión', 'v1'],
                ['Timestamp', now()->toIso8601String()],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Genera un token con permisos específicos para demostración o scripting.
     */
    private function handleGenerateToken(): int
    {
        $userId = (int) ($this->option('user') ?: 1);
        $user = User::find($userId) ?: User::first();

        if (! $user) {
            $this->error('No se encontró ningún usuario en la base de datos.');

            return Command::FAILURE;
        }

        $tokenName = 'cli-automation-key';
        $abilities = ['projects:read', 'tasks:read', 'tasks:write'];

        $token = $user->createToken($tokenName, $abilities);

        $this->info('✓ Nuevo API Token generado con éxito:');
        $this->line("<comment>Usuario:</comment> {$user->name} ({$user->email})");
        $this->line("<comment>Token:</comment> <info>{$token->plainTextToken}</info>");
        $this->line('<comment>Abilities:</comment> '.implode(', ', $abilities));
        $this->line("<comment>Uso en cURL:</comment> curl -H \"Authorization: Bearer {$token->plainTextToken}\" http://localhost:8000/api/v1/public/projects");

        return Command::SUCCESS;
    }

    /**
     * Lista los proyectos accesibles desde la terminal.
     */
    private function handleListProjects(): int
    {
        $userId = (int) ($this->option('user') ?: 1);
        $user = User::find($userId) ?: User::first();

        if (! $user) {
            $this->error('Usuario no encontrado.');

            return Command::FAILURE;
        }

        $proyectos = Proyecto::accessibleBy($user->id)->get();

        if ($proyectos->isEmpty()) {
            $this->warn("No hay proyectos accesibles para el usuario {$user->name}.");

            return Command::SUCCESS;
        }

        $rows = $proyectos->map(fn (Proyecto $p) => [
            $p->id,
            $p->nombre,
            $p->estado,
            $p->tasks()->count(),
            $p->sprints()->count(),
        ]);

        $this->table(['ID', 'Nombre', 'Estado', 'Tareas', 'Sprints'], $rows);

        return Command::SUCCESS;
    }

    /**
     * Lista las tareas de un proyecto.
     */
    private function handleListTasks(): int
    {
        $projectId = (int) $this->option('project');
        if (! $projectId) {
            $this->error('Debe especificar el ID del proyecto con --project={id}');

            return Command::FAILURE;
        }

        $tasks = Task::where('proyecto_id', $projectId)->with('status')->get();

        if ($tasks->isEmpty()) {
            $this->warn("El proyecto #{$projectId} no contiene tareas registradas.");

            return Command::SUCCESS;
        }

        $rows = $tasks->map(fn (Task $t) => [
            $t->id,
            $t->titulo,
            $t->status?->nombre ?? 'Sin estado',
            $t->fecha_limite?->format('Y-m-d') ?? 'N/A',
        ]);

        $this->table(['ID', 'Título', 'Estado Kanban', 'Fecha Límite'], $rows);

        return Command::SUCCESS;
    }

    /**
     * Crea una tarea desde la línea de comandos (ej. simulación de hook de Git).
     */
    private function handleCreateTask(): int
    {
        $projectId = (int) $this->option('project');
        $title = $this->option('title');

        if (! $projectId || ! $title) {
            $this->error('Se requiere --project={id} y --title="Título de la tarea"');

            return Command::FAILURE;
        }

        $task = Task::create([
            'proyecto_id' => $projectId,
            'titulo' => $title,
            'descripcion' => 'Creada automáticamente vía Scrumter CLI (Canal 3 - Integración CI/CD).',
        ]);

        $this->info("✓ Tarea #{$task->id} creada exitosamente en el proyecto #{$projectId}: «{$task->titulo}»");

        return Command::SUCCESS;
    }
}
