<?php

namespace Tests\Unit\Contexts\AiAssistant;

use App\Contexts\AiAssistant\Infrastructure\Adapters\EloquentDraftStorageAdapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentDraftStorageAdapterTest extends TestCase
{
    use RefreshDatabase;

    private EloquentDraftStorageAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new EloquentDraftStorageAdapter;
    }

    public function test_it_persists_and_retrieves_draft_using_real_schema(): void
    {
        $user = User::factory()->create();
        $projectId = 15;
        $proposal = [
            'original_prompt' => 'Crear plataforma de gestión Scrum',
            'project' => ['nombre' => 'Scrumter Core'],
            'requirements' => [
                ['titulo' => 'Autenticación segura', 'tipo' => 'seguridad'],
            ],
        ];

        $draftId = $this->adapter->saveDraft($projectId, $user->id, $proposal);

        $this->assertDatabaseHas('ai_project_drafts', [
            'id' => $draftId,
            'user_id' => $user->id,
            'status' => 'project_ready',
            'original_prompt' => 'Crear plataforma de gestión Scrum',
        ]);

        $retrieved = $this->adapter->findDraft($draftId);

        $this->assertNotNull($retrieved);
        $this->assertSame($draftId, $retrieved['id']);
        $this->assertSame($projectId, $retrieved['proyecto_id']);
        $this->assertSame($user->id, $retrieved['user_id']);
        $this->assertSame($proposal, $retrieved['proposal']);
        $this->assertSame('project_ready', $retrieved['status']);
        $this->assertFalse($retrieved['is_applied']);
    }

    public function test_it_marks_draft_as_applied_updating_status_to_completed(): void
    {
        $user = User::factory()->create();
        $draftId = $this->adapter->saveDraft(8, $user->id, ['requirements' => []]);

        $marked = $this->adapter->markAsApplied($draftId);

        $this->assertTrue($marked);
        $this->assertDatabaseHas('ai_project_drafts', [
            'id' => $draftId,
            'status' => 'completed',
        ]);

        $updated = $this->adapter->findDraft($draftId);
        $this->assertTrue($updated['is_applied']);
        $this->assertSame('completed', $updated['status']);
    }

    public function test_it_handles_missing_draft_gracefully(): void
    {
        $this->assertNull($this->adapter->findDraft(999999));
        $this->assertFalse($this->adapter->markAsApplied(999999));
    }
}
