<?php

namespace Tests\Feature\ScrumCore;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProyectoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_owner_can_create_show_update_and_delete_project_with_audit(): void
    {
        $owner = User::factory()->create();

        // 1. Crear proyecto por flujo Web
        $createResponse = $this->actingAs($owner)->post('/proyectos', [
            'nombre' => 'Proyecto Hexagonal',
            'descripcion' => 'Descripción inicial',
            'estado' => 'activo',
        ]);

        $proyecto = Proyecto::where('nombre', 'Proyecto Hexagonal')->first();
        $this->assertNotNull($proyecto);
        $createResponse->assertRedirect(route('proyectos.show', $proyecto));

        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $proyecto->id,
            'user_id' => $owner->id,
            'action' => 'created',
        ]);

        // 2. Listar y visualizar detalle (tablero Kanban)
        $this->actingAs($owner)->get('/proyectos')->assertOk()->assertSee('Proyecto Hexagonal');
        $this->actingAs($owner)->get("/proyectos/{$proyecto->id}")->assertOk()->assertSee('Proyecto Hexagonal');

        // 3. Actualizar proyecto vía UpdateProjectUseCase
        $updateResponse = $this->actingAs($owner)->put("/proyectos/{$proyecto->id}", [
            'nombre' => 'Proyecto Hexagonal v2',
            'descripcion' => 'Descripción actualizada',
            'estado' => 'pausado',
        ]);

        $updateResponse->assertRedirect(route('proyectos.show', $proyecto));
        $this->assertDatabaseHas('proyectos', [
            'id' => $proyecto->id,
            'nombre' => 'Proyecto Hexagonal v2',
            'estado' => 'pausado',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $proyecto->id,
            'action' => 'updated',
        ]);

        // 4. Eliminar proyecto vía DeleteProjectUseCase
        $deleteResponse = $this->actingAs($owner)->delete("/proyectos/{$proyecto->id}");

        $deleteResponse->assertRedirect(route('proyectos.index'));
        $this->assertDatabaseMissing('proyectos', ['id' => $proyecto->id]);
    }

    public function test_web_non_owner_cannot_view_update_or_delete_foreign_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $proyecto = Proyecto::create([
            'user_id' => $owner->id,
            'nombre' => 'Proyecto Privado',
            'estado' => 'activo',
        ]);

        $this->actingAs($intruder)->get("/proyectos/{$proyecto->id}")->assertForbidden();
        $this->actingAs($intruder)->put("/proyectos/{$proyecto->id}", [
            'nombre' => 'Secuestrado',
            'estado' => 'activo',
        ])->assertForbidden();
        $this->actingAs($intruder)->delete("/proyectos/{$proyecto->id}")->assertForbidden();

        $this->assertDatabaseHas('proyectos', [
            'id' => $proyecto->id,
            'nombre' => 'Proyecto Privado',
        ]);
    }

    public function test_api_channel_creates_lists_and_shows_accessible_projects_via_use_cases(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($owner);

        // Creación vía API usando CreateProjectUseCase
        $storeResponse = $this->postJson('/api/proyectos', [
            'name' => 'Proyecto Móvil API',
            'description' => 'Creado desde Android',
            'status' => 'activo',
        ]);

        $storeResponse->assertCreated()->assertJsonPath('project.nombre', 'Proyecto Móvil API');
        $projectId = $storeResponse->json('project.id');

        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $projectId,
            'user_id' => $owner->id,
            'action' => 'created',
        ]);

        // Listado y detalle accesible
        $this->getJson('/api/proyectos')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Proyecto Móvil API']);

        $this->getJson("/api/proyectos/{$projectId}")
            ->assertOk()
            ->assertJsonPath('nombre', 'Proyecto Móvil API');

        // Usuario externo sin acceso recibe 404
        Sanctum::actingAs($otherUser);
        $this->getJson("/api/proyectos/{$projectId}")->assertNotFound();
    }
}
