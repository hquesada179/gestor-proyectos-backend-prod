<?php

namespace Tests\Feature\Ai;

use App\Models\AiProjectChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_their_chat_history(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Se crean registros para el usuario autenticado y para otro usuario ajeno
        AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Proyecto A',
            'estado' => 'generado',
        ]);

        AiProjectChat::create([
            'user_id' => $otherUser->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Proyecto B',
            'estado' => 'generado',
        ]);

        $response = $this->actingAs($user)->getJson(route('asistente-ia.historial'));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'history');
        $this->assertEquals('Proyecto A', $response->json('history.0.prompt_completo'));
    }

    public function test_user_cannot_view_another_users_history_detail(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Registro perteneciente a otro usuario para verificar la barrera de autorización
        $record = AiProjectChat::create([
            'user_id' => $otherUser->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Privado',
            'estado' => 'generado',
        ]);

        $response = $this->actingAs($user)->getJson(route('asistente-ia.historial.detail', $record->id));

        $response->assertStatus(404);
    }

    public function test_user_can_delete_their_history_item(): void
    {
        $user = User::factory()->create();

        $record = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'Por borrar',
            'estado' => 'generado',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('asistente-ia.historial.delete', $record->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ai_project_chats', ['id' => $record->id]);
    }
}
