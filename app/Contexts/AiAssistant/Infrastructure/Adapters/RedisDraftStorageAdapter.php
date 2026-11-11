<?php

namespace App\Contexts\AiAssistant\Infrastructure\Adapters;

use App\Contexts\AiAssistant\Domain\Ports\DraftStorageInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Adaptador de persistencia NoSQL / Clave-Valor para borradores semiestructurados de IA.
 * Almacena propuestas JSON volátiles en memoria (Redis) con TTL de expiración para evitar
 * sobrecargar la base de datos relacional con borradores no confirmados.
 */
class RedisDraftStorageAdapter implements DraftStorageInterface
{
    private readonly CacheRepository $cache;

    public function __construct(?CacheRepository $cache = null)
    {
        // Se utiliza la conexión de caché configurada (Redis en producción, store local en tests)
        $this->cache = $cache ?: Cache::store();
    }

    /**
     * Guarda el borrador semiestructurado en formato JSON con TTL de 24 horas.
     */
    public function saveDraft(int $projectId, int $userId, array $content): int
    {
        // Generación de identificador único determinista para el borrador
        $counterKey = 'ai:drafts:id_seq';
        $draftId = (int) ($this->cache->get($counterKey, 1000) + 1);
        $this->cache->put($counterKey, $draftId, now()->addYear());

        $payload = [
            'id' => $draftId,
            'proyecto_id' => $projectId,
            'user_id' => $userId,
            'proposal' => $content,
            'is_applied' => false,
            'created_at' => now()->toIso8601String(),
        ];

        // Persistir en el almacén clave-valor con ventana de vida útil de 24 horas
        $this->cache->put($this->draftKey($draftId), json_encode($payload), now()->addDay());

        return $draftId;
    }

    /**
     * Recupera y deserializa el borrador desde la memoria Redis.
     */
    public function findDraft(int $draftId): ?array
    {
        $raw = $this->cache->get($this->draftKey($draftId));
        if (! $raw) {
            // Early return si el borrador expiró o no existe
            return null;
        }

        return json_decode((string) $raw, true);
    }

    /**
     * Actualiza la bandera de aplicación del borrador una vez consolidado en Scrum Core.
     */
    public function markAsApplied(int $draftId): bool
    {
        $draft = $this->findDraft($draftId);
        if (! $draft) {
            return false;
        }

        $draft['is_applied'] = true;
        $draft['applied_at'] = now()->toIso8601String();

        $this->cache->put($this->draftKey($draftId), json_encode($draft), now()->addDay());

        return true;
    }

    private function draftKey(int $id): string
    {
        return "ai:draft:{$id}";
    }
}
