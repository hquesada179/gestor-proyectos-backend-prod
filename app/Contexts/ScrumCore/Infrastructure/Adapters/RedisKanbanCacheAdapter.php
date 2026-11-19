<?php

namespace App\Contexts\ScrumCore\Infrastructure\Adapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Adaptador de infraestructura para almacenamiento en caché de tableros Kanban en Redis.
 * Mitiga la contención de lecturas en la base de datos relacional durante ceremonias de equipo.
 */
class RedisKanbanCacheAdapter
{
    private readonly CacheRepository $cache;

    public function __construct(?CacheRepository $cache = null)
    {
        $this->cache = $cache ?: Cache::store();
    }

    /**
     * Obtiene el estado del tablero desde el almacén en memoria o lo genera mediante callback.
     *
     * @param  int  $projectId  ID del proyecto
     * @param  callable(): Collection  $callback  Función de consulta relacional en caso de fallo de caché (cache miss)
     * @param  int  $ttlSeconds  Tiempo de expiración de caché en segundos (por defecto 15 minutos)
     */
    public function rememberKanban(int $projectId, callable $callback, int $ttlSeconds = 900): Collection
    {
        $key = $this->kanbanKey($projectId);

        $cached = $this->cache->get($key);
        if ($cached !== null) {
            // Retornar datos deserializados desde la caché de ultra-baja latencia
            return collect(json_decode((string) $cached, true));
        }

        // Ejecutar consulta relacional si hubo cache miss
        /** @var Collection $fresh */
        $fresh = $callback();

        // Almacenar el resultado serializado en memoria
        $this->cache->put($key, json_encode($fresh->toArray()), now()->addSeconds($ttlSeconds));

        return $fresh;
    }

    /**
     * Invalida activamente la caché del tablero cuando una tarea cambia de estado.
     */
    public function invalidateKanban(int $projectId): void
    {
        $this->cache->forget($this->kanbanKey($projectId));
    }

    private function kanbanKey(int $projectId): string
    {
        return "kanban:board:project:{$projectId}";
    }
}
