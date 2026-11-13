<?php

namespace App\Jobs;

use App\Contexts\AiAssistant\Application\DTOs\GenerateProjectDraftDTO;
use App\Contexts\AiAssistant\Application\UseCases\GenerateProjectDraftUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trabajo en segundo plano para procesar la inferencia masiva de proyectos con IA.
 * Desacopla el cómputo pesado del ciclo de petición HTTP con reintentos exponenciales.
 */
class ProcessAiProjectGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de reintentos antes de enviar a Dead Letter Queue (failed_jobs).
     */
    public int $tries = 3;

    /**
     * Estrategia de retroceso exponencial (segundos entre reintentos).
     */
    public array $backoff = [5, 15, 30];

    /**
     * Tiempo máximo de ejecución del job antes de timeout.
     */
    public int $timeout = 120;

    public function __construct(
        public readonly int $userId,
        public readonly int $projectId,
        public readonly string $inputBrief,
        public readonly string $targetLanguage = 'es'
    ) {}

    public function handle(GenerateProjectDraftUseCase $useCase): void
    {
        Log::info("ProcessAiProjectGenerationJob: Iniciando procesamiento asíncrono para proyecto #{$this->projectId}");

        $dto = new GenerateProjectDraftDTO(
            userId: $this->userId,
            projectId: $this->projectId,
            inputBrief: $this->inputBrief,
            targetLanguage: $this->targetLanguage
        );

        // Se delega al caso de uso de la arquitectura hexagonal
        $result = $useCase->execute($dto);

        Log::info("ProcessAiProjectGenerationJob: Borrador #{$result['draft_id']} generado exitosamente en background.");
    }

    /**
     * Tratamiento de fallo definitivo tras agotar todos los reintentos.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("ProcessAiProjectGenerationJob: Fallo definitivo para proyecto #{$this->projectId}", [
            'error' => $exception?->getMessage(),
            'user_id' => $this->userId,
        ]);
    }
}
