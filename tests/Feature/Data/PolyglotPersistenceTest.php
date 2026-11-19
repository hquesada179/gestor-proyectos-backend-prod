<?php

namespace Tests\Feature\Data;

use App\Contexts\AiAssistant\Infrastructure\Adapters\RedisDraftStorageAdapter;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PolyglotPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dual_engine_persistence_operates_in_harmony(): void
    {
        // ── MOTOR 1: Persistencia Relacional (SQL ACID) ──────────────────────
        $user = User::factory()->create([
            'email' => 'architect@scrumter.io',
        ]);

        $proyecto = Proyecto::create([
            'user_id' => $user->id,
            'nombre' => 'Proyecto Transaccional Políglota',
            'estado' => 'activo',
        ]);

        // Verificación en motor SQL
        $this->assertDatabaseHas('proyectos', [
            'id' => $proyecto->id,
            'nombre' => 'Proyecto Transaccional Políglota',
            'user_id' => $user->id,
        ]);

        // ── MOTOR 2: Persistencia Clave-Valor / Documental (Redis / In-Memory) ─
        $draftAdapter = new RedisDraftStorageAdapter(Cache::store('array'));

        $semiStructuredAiData = [
            'model' => 'gpt-4.1-mini',
            'temperature' => 0.7,
            'requirements' => [
                ['code' => 'REQ-01', 'title' => 'Integración Políglota'],
            ],
            'metadata' => [
                'tokens_prompt' => 450,
                'tokens_completion' => 120,
            ],
        ];

        $draftId = $draftAdapter->saveDraft($proyecto->id, $user->id, $semiStructuredAiData);

        // Verificación en motor NoSQL / Clave-Valor
        $retrievedDraft = $draftAdapter->findDraft($draftId);

        $this->assertNotNull($retrievedDraft);
        $this->assertEquals($proyecto->id, $retrievedDraft['proyecto_id']);
        $this->assertEquals($user->id, $retrievedDraft['user_id']);
        $this->assertEquals($semiStructuredAiData, $retrievedDraft['proposal']);

        // Comprobar que no contamina la base de datos relacional
        $this->assertDatabaseMissing('proyectos', [
            'nombre' => 'Integración Políglota',
        ]);
    }
}
