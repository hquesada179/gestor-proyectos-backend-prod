<?php

namespace App\Contexts\AiAssistant\Infrastructure\Adapters;

use App\Contexts\AiAssistant\Domain\Ports\DraftStorageInterface;
use App\Models\AiProjectDraft;

/**
 * Adaptador de infraestructura para persistencia de borradores de IA usando Eloquent.
 */
class EloquentDraftStorageAdapter implements DraftStorageInterface
{
    public function saveDraft(int $projectId, int $userId, array $content): int
    {
        // Se almacena el payload estructurado como borrador activo
        $draft = AiProjectDraft::create([
            'proyecto_id' => $projectId,
            'user_id' => $userId,
            'proposal' => $content,
            'is_applied' => false,
        ]);

        return $draft->id;
    }

    public function findDraft(int $draftId): ?array
    {
        $draft = AiProjectDraft::find($draftId);
        if (! $draft) {
            // Early return si no se localiza el borrador
            return null;
        }

        return [
            'id' => $draft->id,
            'proyecto_id' => $draft->proyecto_id,
            'user_id' => $draft->user_id,
            'proposal' => $draft->proposal,
            'is_applied' => (bool) $draft->is_applied,
        ];
    }

    public function markAsApplied(int $draftId): bool
    {
        $draft = AiProjectDraft::find($draftId);
        if (! $draft) {
            return false;
        }

        return $draft->update(['is_applied' => true]);
    }
}
