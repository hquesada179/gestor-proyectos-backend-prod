<?php

namespace Tests\Feature\Ai;

use App\Models\AiProjectChat;
use App\Models\AiUsageLog;
use App\Models\Proyecto;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AiAdversarialStressTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_or_whitespace_requirements_are_rejected(): void
    {
        $user = User::factory()->create();

        // Propuesta maliciosa con títulos en blanco que intentan evadir el filtro
        $malformedProposal = [
            '_tipo' => 'create',
            'nombre' => 'Proyecto Trampa',
            'descripcion' => 'Descripción válida',
            'requerimientos' => [
                ['titulo' => '   ', 'tipo' => 'funcional'],
                ['titulo' => '', 'tipo' => 'funcional'],
            ],
            'tareas' => [
                ['titulo' => '   '],
            ],
        ];

        $record = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Intento con datos vacíos',
            'estado' => 'borrador',
            'datos_detectados' => $malformedProposal,
        ]);

        $response = $this->actingAs($user)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'confirm',
            'data' => $malformedProposal,
        ]);

        $response->assertStatus(422);
        // Garantizamos que ningún proyecto fantasma quedó guardado en la base de datos
        $this->assertDatabaseMissing('proyectos', ['nombre' => 'Proyecto Trampa']);
        $this->assertEquals('error', $record->fresh()->estado);
    }

    public function test_cannot_apply_proposal_twice_preventing_duplicate_projects(): void
    {
        $user = User::factory()->create();

        $proposal = [
            '_tipo' => 'create',
            'nombre' => 'Proyecto Único',
            'descripcion' => 'No debe duplicarse',
            'requerimientos' => [['titulo' => 'Req 1', 'tipo' => 'funcional']],
            'tareas' => [['titulo' => 'Tarea 1']],
        ];

        $record = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Crear proyecto',
            'estado' => 'borrador',
            'datos_detectados' => $proposal,
        ]);

        // Primera llamada: éxito
        $firstResponse = $this->actingAs($user)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'confirm',
            'data' => $proposal,
        ]);
        $firstResponse->assertStatus(200);

        // Segunda llamada repetida (simulación de doble clic o reintento de red): debe rebotar
        $secondResponse = $this->actingAs($user)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'confirm',
            'data' => $proposal,
        ]);

        $secondResponse->assertStatus(422);
        $secondResponse->assertJson(['ok' => false]);

        // Confirmamos que en la base de datos existe exactamente 1 proyecto y no 2
        $this->assertEquals(1, Proyecto::where('nombre', 'Proyecto Único')->count());
    }

    public function test_cross_user_project_tampering_is_prevented(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create();

        // Proyecto privado de la víctima
        $victimProject = Proyecto::create([
            'user_id' => $victim->id,
            'nombre' => 'Proyecto Víctima',
            'descripcion' => 'Secreto',
            'estado' => 'activo',
        ]);

        // El atacante genera una propuesta de mejora apuntando al proyecto de la víctima
        $maliciousImprovement = [
            '_tipo' => 'improve',
            '_proyecto_id' => $victimProject->id,
            'requerimientos_nuevos' => [
                ['titulo' => 'Backdoor inyectado', 'tipo' => 'funcional'],
            ],
            'tareas_nuevas' => [
                ['titulo' => 'Extraer secretos'],
            ],
        ];

        $record = AiProjectChat::create([
            'user_id' => $attacker->id,
            'tipo_accion' => 'editar_proyecto',
            'prompt_usuario' => 'Modificar proyecto ajeno',
            'estado' => 'borrador',
            'datos_detectados' => $maliciousImprovement,
        ]);

        $response = $this->actingAs($attacker)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'confirm',
            'data' => $maliciousImprovement,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['ok' => false]);

        // El proyecto de la víctima no debe haber recibido ninguna tarea ni requerimiento del atacante
        $this->assertCount(0, $victimProject->requirements);
        $this->assertCount(0, $victimProject->tasks);
    }

    public function test_cross_project_task_tampering_is_isolated_in_project_edit(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Proyecto A del usuario autenticado
        $projectA = Proyecto::create([
            'user_id' => $user->id,
            'nombre' => 'Proyecto A',
            'descripcion' => 'Propio',
            'estado' => 'activo',
        ]);

        // Proyecto B de otro usuario con su propia tarea
        $projectB = Proyecto::create([
            'user_id' => $otherUser->id,
            'nombre' => 'Proyecto B',
            'descripcion' => 'Ajeno',
            'estado' => 'activo',
        ]);

        $status = TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#ccc', 'orden' => 1]);

        $victimTask = $projectB->tasks()->create([
            'titulo' => 'Tarea Original Intocable',
            'descripcion' => 'Descripción original',
            'task_status_id' => $status->id,
        ]);

        // El usuario envía edición de su Proyecto A, pero incluye el _id de la tarea del Proyecto B
        $editPayload = [
            '_tipo' => 'project_edit',
            '_proyecto_id' => $projectA->id,
            'nombre' => 'Proyecto A Modificado',
            'requerimientos' => [
                ['titulo' => 'Req 1', 'tipo' => 'funcional'],
            ],
            'tareas' => [
                ['_id' => $victimTask->id, 'titulo' => 'Intento de Sobrescritura Ilícita'],
            ],
        ];

        $record = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'editar_proyecto',
            'prompt_usuario' => 'Editar proyecto A',
            'estado' => 'borrador',
            'datos_detectados' => $editPayload,
        ]);

        $response = $this->actingAs($user)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'confirm',
            'data' => $editPayload,
        ]);

        $response->assertStatus(200);

        // La tarea del Proyecto B no debe haber sido modificada jamás
        $this->assertEquals('Tarea Original Intocable', $victimTask->fresh()->titulo);
    }

    public function test_disallowed_model_is_rejected_across_ai_endpoints(): void
    {
        $user = User::factory()->create();
        $forbiddenModel = 'gpt-4.5-malicious';
        $minimalProposal = ['_tipo' => 'create', 'nombre' => 'Demo'];

        $this->actingAs($user)
            ->postJson(route('asistente-ia.submit'), [
                'modo' => 'crear',
                'prompt' => 'Crear una plataforma de gestión ágil completa.',
                'modelo' => $forbiddenModel,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('modelo');

        $this->actingAs($user)
            ->postJson(route('asistente-ia.refine'), [
                'proposal' => $minimalProposal,
                'instruction' => 'Agrega una tarea extra',
                'model' => $forbiddenModel,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('model');

        $this->actingAs($user)
            ->postJson(route('asistente-ia.regenerate-section'), [
                'proposal' => $minimalProposal,
                'section' => 'tareas',
                'model' => $forbiddenModel,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('model');

        $this->actingAs($user)
            ->postJson(route('asistente-ia.regenerate-item'), [
                'proposal' => $minimalProposal,
                'section' => 'tareas',
                'item' => ['titulo' => 'Tarea 1'],
                'model' => $forbiddenModel,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('model');

        $this->actingAs($user)
            ->postJson(route('asistente-ia.generar'), [
                'descripcion' => 'Descripción larga suficiente para pasar la longitud mínima.',
                'modelo' => $forbiddenModel,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('modelo');

        $this->actingAs($user)
            ->postJson(route('asistente-ia.interpretar'), [
                'idea' => 'Idea suficientemente larga para probar la allowlist.',
                'modelo' => $forbiddenModel,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('modelo');

        $this->assertSame(0, AiProjectChat::count());
        $this->assertSame(0, AiUsageLog::count());
    }

    public function test_submit_rejects_inaccessible_project_or_foreign_parent_chat_without_creating_records(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create();

        $victimProject = Proyecto::create([
            'user_id' => $victim->id,
            'nombre' => 'Proyecto Privado',
            'descripcion' => 'Solo propietario',
            'estado' => 'activo',
        ]);

        $victimChat = AiProjectChat::create([
            'user_id' => $victim->id,
            'proyecto_id' => $victimProject->id,
            'tipo_accion' => 'editar_proyecto',
            'prompt_usuario' => 'Mejora privada',
            'estado' => 'borrador',
        ]);

        $allowedModel = config('services.openai.model', 'gpt-4.1-mini');

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.submit'), [
                'modo' => 'mejorar',
                'prompt' => 'Intenta mejorar el proyecto de otra persona.',
                'modelo' => $allowedModel,
                'proyecto_id' => $victimProject->id,
            ])
            ->assertStatus(404);

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.submit'), [
                'modo' => 'crear',
                'prompt' => 'Intenta vincular un proyecto ajeno al crear.',
                'modelo' => $allowedModel,
                'proyecto_id' => $victimProject->id,
            ])
            ->assertStatus(404);

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.submit'), [
                'modo' => 'mejorar',
                'prompt' => 'Intenta continuar una conversación ajena.',
                'modelo' => $allowedModel,
                'parent_chat_id' => $victimChat->id,
            ])
            ->assertStatus(404);

        // Ninguno de los intentos rechazados debe crear chats ni registros de uso para el atacante.
        $this->assertSame(0, AiProjectChat::where('user_id', $attacker->id)->count());
        $this->assertSame(0, AiUsageLog::where('user_id', $attacker->id)->count());
    }

    public function test_proposal_editors_reject_inaccessible_project_context(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create();

        $victimProject = Proyecto::create([
            'user_id' => $victim->id,
            'nombre' => 'Proyecto Protegido',
            'descripcion' => 'No editable por terceros',
            'estado' => 'activo',
        ]);

        $tamperedProposal = [
            '_tipo' => 'improve',
            '_proyecto_id' => $victimProject->id,
            'nombre' => 'Manipulado',
        ];

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.refine'), [
                'proposal' => $tamperedProposal,
                'instruction' => 'Agrega nuevas tareas',
            ])
            ->assertStatus(404);

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.regenerate-section'), [
                'proposal' => $tamperedProposal,
                'section' => 'tareas_nuevas',
            ])
            ->assertStatus(404);

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.regenerate-item'), [
                'proposal' => $tamperedProposal,
                'section' => 'tareas_nuevas',
                'item' => ['titulo' => 'Elemento'],
            ])
            ->assertStatus(404);

        $this->assertSame(0, AiUsageLog::count());
    }

    public function test_interpretar_route_enforces_ai_generation_throttle(): void
    {
        $route = Route::getRoutes()->getByName('asistente-ia.interpretar');

        $this->assertNotNull($route);
        $this->assertContains('throttle:ai-generation', $route->gatherMiddleware());

        $user = User::factory()->create();

        // Limpiamos la caché de límites al finalizar para no afectar pruebas posteriores con user_id=1.
        Cache::flush();

        try {
            // El limitador ai-generation permite 10 peticiones por minuto; la undécima debe retornar 429.
            for ($i = 0; $i < 10; $i++) {
                $this->actingAs($user)
                    ->postJson(route('asistente-ia.interpretar'), [])
                    ->assertStatus(422);
            }

            $this->actingAs($user)
                ->postJson(route('asistente-ia.interpretar'), [])
                ->assertStatus(429);
        } finally {
            Cache::flush();
        }
    }

    public function test_apply_rejects_foreign_chat_with_404(): void
    {
        $attacker = User::factory()->create();
        $owner = User::factory()->create();

        $record = AiProjectChat::create([
            'user_id' => $owner->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Borrador del propietario',
            'estado' => 'borrador',
        ]);

        $this->actingAs($attacker)
            ->postJson(route('asistente-ia.apply'), [
                'chat_id' => $record->id,
                'action' => 'confirm',
            ])
            ->assertStatus(404);
    }
}
