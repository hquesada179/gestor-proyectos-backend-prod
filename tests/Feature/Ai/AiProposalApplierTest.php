<?php

namespace Tests\Feature\Ai;

use App\Models\AiProjectChat;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProposalApplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_apply_create_proposal_to_generate_project(): void
    {
        $user = User::factory()->create();

        // Borrador de propuesta inicial generado por el asistente
        $proposalData = [
            '_tipo' => 'create',
            'nombre' => 'Sistema de Reservas',
            'descripcion' => 'Plataforma para reservar salas de reunión.',
            'requerimientos' => [
                ['titulo' => 'Autenticación de usuarios', 'tipo' => 'funcional', 'prioridad' => 'alta'],
                ['titulo' => 'Tiempo de respuesta menor a 500ms', 'tipo' => 'no_funcional', 'prioridad' => 'media'],
            ],
            'tareas' => [
                ['titulo' => 'Crear migración de reservas', 'descripcion' => 'Definir campos de fecha y hora'],
            ],
            'sprints' => [
                ['nombre' => 'Sprint 1', 'objetivo' => 'Base del sistema', 'semanas' => 2],
            ],
            'insumos' => [
                ['titulo' => 'Servidor VPS', 'tipo' => 'hardware', 'contenido' => 'Ubuntu 24.04'],
            ],
        ];

        $record = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Crear sistema de reservas',
            'estado' => 'borrador',
            'datos_detectados' => $proposalData,
        ]);

        $response = $this->actingAs($user)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'confirm',
            'data' => $proposalData,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        // Verificamos que el proyecto y sus entidades hijas fueron creados correctamente en SQLite
        $this->assertDatabaseHas('proyectos', [
            'nombre' => 'Sistema de Reservas',
            'user_id' => $user->id,
        ]);

        $proyecto = Proyecto::where('nombre', 'Sistema de Reservas')->first();
        $this->assertNotNull($proyecto);
        $this->assertCount(2, $proyecto->requirements);
        $this->assertCount(1, $proyecto->tasks);
        $this->assertCount(1, $proyecto->sprints);
        $this->assertCount(1, $proyecto->inputs);

        // Verificamos que el registro de chat se marcó como aplicado
        $this->assertEquals('aplicado', $record->fresh()->estado);
        $this->assertEquals($proyecto->id, $record->fresh()->proyecto_id);
    }

    public function test_user_can_discard_proposal(): void
    {
        $user = User::factory()->create();

        $record = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Propuesta a descartar',
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($user)->postJson(route('asistente-ia.apply'), [
            'chat_id' => $record->id,
            'action' => 'discard',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'message' => 'Propuesta descartada.']);
        $this->assertEquals('descartado', $record->fresh()->estado);
    }

    public function test_load_project_structure_fails_for_inaccessible_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Proyecto de otro usuario sin membresía del usuario actual
        $proyecto = Proyecto::create([
            'user_id' => $otherUser->id,
            'nombre' => 'Proyecto Privado',
            'descripcion' => 'No visible para otros',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user)->getJson(route('asistente-ia.proyecto.estructura', $proyecto->id));

        $response->assertStatus(404);
    }

    public function test_load_project_structure_succeeds_for_accessible_project(): void
    {
        $user = User::factory()->create();

        $proyecto = Proyecto::create([
            'user_id' => $user->id,
            'nombre' => 'Proyecto Propio',
            'descripcion' => 'Estructura editable',
            'estado' => 'activo',
        ]);

        $proyecto->requirements()->create([
            'titulo' => 'Requerimiento 1',
            'descripcion' => 'Descripción del requerimiento',
            'tipo' => 'funcional',
            'prioridad' => 'alta',
        ]);

        $response = $this->actingAs($user)->getJson(route('asistente-ia.proyecto.estructura', $proyecto->id));

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'proyecto_nombre' => 'Proyecto Propio',
        ]);
        $response->assertJsonPath('counts.requerimientos', 1);
    }
}
