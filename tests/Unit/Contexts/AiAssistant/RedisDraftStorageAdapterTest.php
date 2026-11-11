<?php

namespace Tests\Unit\Contexts\AiAssistant;

use App\Contexts\AiAssistant\Infrastructure\Adapters\RedisDraftStorageAdapter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RedisDraftStorageAdapterTest extends TestCase
{
    private RedisDraftStorageAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        // Usar almacén en memoria para pruebas unitarias aisladas
        $this->adapter = new RedisDraftStorageAdapter(Cache::store('array'));
    }

    public function test_it_saves_and_retrieves_draft_as_json_document(): void
    {
        $projectId = 10;
        $userId = 42;
        $proposal = [
            'requirements' => [
                ['titulo' => 'Login biométrico', 'tipo' => 'seguridad'],
            ],
            'userStories' => [
                ['titulo' => 'Como usuario quiero autenticarme con huella'],
            ],
        ];

        $draftId = $this->adapter->saveDraft($projectId, $userId, $proposal);

        $this->assertIsInt($draftId);
        $this->assertGreaterThan(1000, $draftId);

        $retrieved = $this->adapter->findDraft($draftId);

        $this->assertNotNull($retrieved);
        $this->assertEquals($projectId, $retrieved['proyecto_id']);
        $this->assertEquals($userId, $retrieved['user_id']);
        $this->assertEquals($proposal, $retrieved['proposal']);
        $this->assertFalse($retrieved['is_applied']);
    }

    public function test_it_marks_draft_as_applied(): void
    {
        $draftId = $this->adapter->saveDraft(1, 1, ['test' => true]);

        $marked = $this->adapter->markAsApplied($draftId);
        $this->assertTrue($marked);

        $updated = $this->adapter->findDraft($draftId);
        $this->assertTrue($updated['is_applied']);
        $this->assertArrayHasKey('applied_at', $updated);
    }

    public function test_find_draft_returns_null_for_non_existent(): void
    {
        $this->assertNull($this->adapter->findDraft(999999));
    }
}
