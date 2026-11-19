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
        $projectPayload = $content['project_data'] ?? $content['project'] ?? [];
        if (! is_array($projectPayload)) {
            $projectPayload = [];
        }

        // original_prompt es obligatorio en ai_project_drafts; si el caller no lo envía se usa un respaldo trazable
        $originalPrompt = (string) ($content['original_prompt'] ?? $content['prompt'] ?? "Borrador IA para proyecto #{$projectId}");

        // Guardamos el proyecto asociado dentro de project_data porque la tabla no tiene columna proyecto_id
        $draft = AiProjectDraft::create([
            'user_id' => $userId,
            'status' => 'project_ready',
            'selected_mode' => $content['selected_mode'] ?? $content['mode'] ?? null,
            'original_prompt' => $originalPrompt,
            'improved_prompt' => $content['improved_prompt'] ?? null,
            'interpretation_data' => $content['interpretation_data'] ?? $content['interpretation'] ?? null,
            'project_data' => array_merge($projectPayload, ['proyecto_id' => $projectId]),
            'requirements_data' => $content['requirements_data'] ?? $content['requirements'] ?? null,
            'tasks_data' => $content['tasks_data'] ?? $content['tasks'] ?? null,
            'sprints_data' => $content['sprints_data'] ?? $content['sprints'] ?? null,
            'supplies_data' => $content['supplies_data'] ?? $content['supplies'] ?? null,
            'raw_responses' => $content,
        ]);

        return $draft->id;
    }

    public function findDraft(int $draftId): ?array
    {
        $draft = AiProjectDraft::find($draftId);
        if (! $draft) {
            return null;
        }

        // Reconstruimos el payload completo desde raw_responses o desde las secciones tipadas si viene de otro flujo
        $proposal = is_array($draft->raw_responses)
            ? $draft->raw_responses
            : array_filter([
                'project' => $draft->project_data,
                'requirements' => $draft->requirements_data,
                'tasks' => $draft->tasks_data,
                'sprints' => $draft->sprints_data,
                'supplies' => $draft->supplies_data,
            ], static fn ($value) => $value !== null);

        return [
            'id' => $draft->id,
            'proyecto_id' => $draft->project_data['proyecto_id'] ?? null,
            'user_id' => $draft->user_id,
            'proposal' => $proposal,
            'status' => $draft->status,
            'is_applied' => $draft->status === 'completed',
        ];
    }

    public function markAsApplied(int $draftId): bool
    {
        $draft = AiProjectDraft::find($draftId);
        if (! $draft) {
            return false;
        }

        return $draft->update(['status' => 'completed']);
    }
}
