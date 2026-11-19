<?php

namespace Tests\Feature\Ai;

use App\Contexts\AiAssistant\Application\UseCases\GenerateProjectDraftUseCase;
use App\Jobs\ProcessAiProjectGenerationJob;
use App\Models\AiProjectChat;
use App\Models\Proyecto;
use App\Models\User;
use App\Models\UserAiCredit;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\OpenAiAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AiAsyncJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_async_endpoint_dispatches_job_and_returns_202(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        UserAiCredit::where('user_id', $user->id)->update(['credits_available' => 5]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/projects/generate-async', [
            'prompt' => 'Construir plataforma de reservas médicas con Scrum',
            'target_language' => 'es',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'queued');

        $chatId = $response->json('chat_id');

        $this->assertDatabaseHas('ai_project_chats', [
            'id' => $chatId,
            'user_id' => $user->id,
            'estado' => 'encolado',
        ]);

        Queue::assertPushed(ProcessAiProjectGenerationJob::class, function (ProcessAiProjectGenerationJob $job) use ($user, $chatId) {
            return $job->userId === $user->id
                && $job->chatId === $chatId
                && $job->targetLanguage === 'es';
        });
    }

    public function test_generate_endpoint_with_async_flag_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        UserAiCredit::where('user_id', $user->id)->update(['credits_available' => 5]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/projects/generate', [
            'prompt' => 'Crear gestor documental para equipo legal',
            'async' => true,
        ]);

        $response->assertStatus(202)->assertJsonPath('status', 'queued');
        Queue::assertPushed(ProcessAiProjectGenerationJob::class);
    }

    public function test_generate_async_rejects_inaccessible_project_without_dispatching_job(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        UserAiCredit::where('user_id', $intruder->id)->update(['credits_available' => 5]);

        $proyecto = Proyecto::create([
            'user_id' => $owner->id,
            'nombre' => 'Proyecto Privado',
            'estado' => 'activo',
        ]);

        Sanctum::actingAs($intruder);

        $this->postJson('/api/ai/projects/generate-async', [
            'prompt' => 'Intentar generar sobre proyecto ajeno',
            'project_id' => $proyecto->id,
        ])->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_generate_async_rejects_when_user_has_no_credits(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        app(AiCreditService::class)->canUseAi($user->id, 'generar_proyecto');
        UserAiCredit::where('user_id', $user->id)->update(['credits_available' => 0]);
        Sanctum::actingAs($user);

        $this->postJson('/api/ai/projects/generate-async', [
            'prompt' => 'Generar proyecto sin saldo de créditos',
        ])->assertStatus(402);

        Queue::assertNothingPushed();
    }

    public function test_job_execution_persists_draft_deducts_credits_and_updates_chat(): void
    {
        $user = User::factory()->create();
        app(AiCreditService::class)->canUseAi($user->id, 'generar_proyecto');
        UserAiCredit::where('user_id', $user->id)->update([
            'credits_available' => 50,
            'credits_used' => 0,
        ]);

        $chat = AiProjectChat::create([
            'user_id' => $user->id,
            'tipo_accion' => 'crear_proyecto',
            'prompt_usuario' => 'ERP ligero para pymes',
            'estado' => 'encolado',
        ]);

        // Mockeamos únicamente la frontera HTTP externa de OpenAI
        $openAiMock = Mockery::mock(OpenAiAssistantService::class);
        $openAiMock->shouldReceive('generate')
            ->once()
            ->andReturn([
                'ok' => true,
                'data' => [
                    'nombre' => 'ERP Pyme',
                    'requirements' => [['titulo' => 'Facturación']],
                ],
            ]);

        $this->app->instance(OpenAiAssistantService::class, $openAiMock);

        $job = new ProcessAiProjectGenerationJob(
            userId: $user->id,
            projectId: 0,
            inputBrief: 'ERP ligero para pymes',
            targetLanguage: 'es',
            chatId: $chat->id
        );

        $job->handle(app(GenerateProjectDraftUseCase::class));

        $this->assertDatabaseHas('ai_project_drafts', [
            'user_id' => $user->id,
            'status' => 'project_ready',
        ]);

        $this->assertDatabaseHas('user_ai_credits', [
            'user_id' => $user->id,
            'credits_available' => 49,
            'credits_used' => 1,
        ]);

        $this->assertDatabaseHas('ai_project_chats', [
            'id' => $chat->id,
            'estado' => 'borrador',
        ]);
    }
}
