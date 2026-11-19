<?php

namespace Tests\Unit\Contexts\ScrumCore;

use App\Contexts\ScrumCore\Infrastructure\Adapters\RedisKanbanCacheAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RedisKanbanCacheAdapterTest extends TestCase
{
    private RedisKanbanCacheAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new RedisKanbanCacheAdapter(Cache::store('array'));
    }

    public function test_it_caches_kanban_result_and_avoids_reexecuting_callback(): void
    {
        $projectId = 7;
        $callCount = 0;

        $dbQuery = function () use (&$callCount) {
            $callCount++;

            return new Collection([
                ['id' => 1, 'titulo' => 'Tarea 1', 'status' => 'Por hacer'],
                ['id' => 2, 'titulo' => 'Tarea 2', 'status' => 'En progreso'],
            ]);
        };

        // Primera llamada: Cache Miss (ejecuta callback relacional)
        $result1 = $this->adapter->rememberKanban($projectId, $dbQuery);
        $this->assertEquals(1, $callCount);
        $this->assertCount(2, $result1);

        // Segunda llamada: Cache Hit (retorna desde memoria sin volver a consultar SQL)
        $result2 = $this->adapter->rememberKanban($projectId, $dbQuery);
        $this->assertEquals(1, $callCount, 'El callback relacional no debe ejecutarse en un cache hit');
        $this->assertCount(2, $result2);
    }

    public function test_it_invalidates_kanban_cache(): void
    {
        $projectId = 8;
        $callCount = 0;

        $dbQuery = function () use (&$callCount) {
            $callCount++;

            return new Collection([['id' => 10, 'titulo' => 'Tarea A']]);
        };

        $this->adapter->rememberKanban($projectId, $dbQuery);
        $this->assertEquals(1, $callCount);

        // Invalidar caché tras movimiento de tarjeta en el tablero
        $this->adapter->invalidateKanban($projectId);

        // Siguiente llamada debe ser Cache Miss y re-ejecutar callback
        $this->adapter->rememberKanban($projectId, $dbQuery);
        $this->assertEquals(2, $callCount, 'Tras invalidar la caché debe reconsultar el origen relacional');
    }
}
