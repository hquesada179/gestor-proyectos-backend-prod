<?php

namespace Tests\Feature\Api;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_endpoint_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/v1/public/ping');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'channel' => 'public_api',
                'version' => 'v1',
            ]);
    }

    public function test_tokens_endpoint_issues_token_with_abilities(): void
    {
        $user = User::factory()->create([
            'email' => 'dev@scrumter.io',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/v1/public/tokens', [
            'email' => 'dev@scrumter.io',
            'password' => 'secret123',
            'token_name' => 'ci-bot',
            'abilities' => ['projects:read', 'tasks:write'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token_type',
                'token',
                'token_name',
                'abilities',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
                'token_name' => 'ci-bot',
                'abilities' => ['projects:read', 'tasks:write'],
            ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/public/projects');

        $response->assertStatus(401);
    }

    public function test_request_without_required_ability_is_forbidden(): void
    {
        $user = User::factory()->create();

        // Token emitido solo con habilidad tasks:read (sin projects:read)
        Sanctum::actingAs($user, ['tasks:read']);

        $response = $this->getJson('/api/v1/public/projects');

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_list_accessible_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myProject = Proyecto::create([
            'user_id' => $user->id,
            'nombre' => 'Mi Proyecto Scrum',
            'estado' => 'activo',
        ]);

        $otherProject = Proyecto::create([
            'user_id' => $otherUser->id,
            'nombre' => 'Proyecto Ajeno',
            'estado' => 'activo',
        ]);

        Sanctum::actingAs($user, ['projects:read']);

        $response = $this->getJson('/api/v1/public/projects');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Mi Proyecto Scrum');
    }

    public function test_creating_task_requires_tasks_write_ability(): void
    {
        $user = User::factory()->create();
        $proyecto = Proyecto::create([
            'user_id' => $user->id,
            'nombre' => 'Proyecto CI',
            'estado' => 'activo',
        ]);

        // Intentar crear tarea con un token de solo lectura (tasks:read)
        Sanctum::actingAs($user, ['tasks:read']);

        $forbiddenResponse = $this->postJson("/api/v1/public/projects/{$proyecto->id}/tasks", [
            'titulo' => 'Tarea desde CI',
        ]);

        $forbiddenResponse->assertStatus(403);

        // Ahora intentar con habilidad tasks:write
        Sanctum::actingAs($user, ['tasks:write']);

        $successResponse = $this->postJson("/api/v1/public/projects/{$proyecto->id}/tasks", [
            'titulo' => 'Tarea creada por GitHub Actions',
            'descripcion' => 'Commit a1b2c3d',
        ]);

        $successResponse->assertStatus(201)
            ->assertJsonPath('data.titulo', 'Tarea creada por GitHub Actions');

        $this->assertDatabaseHas('tasks', [
            'proyecto_id' => $proyecto->id,
            'titulo' => 'Tarea creada por GitHub Actions',
        ]);
    }
}
