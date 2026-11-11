<?php

namespace App\Providers;

use App\Contexts\AiAssistant\Domain\Ports\AIEngineInterface;
use App\Contexts\AiAssistant\Domain\Ports\CreditManagerInterface;
use App\Contexts\AiAssistant\Domain\Ports\DraftStorageInterface;
use App\Contexts\AiAssistant\Infrastructure\Adapters\EloquentCreditManagerAdapter;
use App\Contexts\AiAssistant\Infrastructure\Adapters\EloquentDraftStorageAdapter;
use App\Contexts\AiAssistant\Infrastructure\Adapters\OpenAiEngineAdapter;
use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Contexts\Billing\Infrastructure\Adapters\EloquentSubscriptionRepository;
use App\Contexts\Billing\Infrastructure\Adapters\WompiGatewayAdapter;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use App\Contexts\ScrumCore\Domain\Ports\TaskRepositoryInterface;
use App\Contexts\ScrumCore\Infrastructure\Adapters\EloquentProjectRepository;
use App\Contexts\ScrumCore\Infrastructure\Adapters\EloquentTaskRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para inyección de dependencias de la Arquitectura Hexagonal.
 * Conecta los puertos de dominio con sus respectivos adaptadores de infraestructura.
 */
class ContextsServiceProvider extends ServiceProvider
{
    /**
     * Registra los bindings de los 3 Bounded Contexts en el Service Container de Laravel.
     */
    public function register(): void
    {
        // ── 1. Bounded Context: Scrum Core ─────────────────────────────────────
        $this->app->bind(ProjectRepositoryInterface::class, EloquentProjectRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, EloquentTaskRepository::class);

        // ── 2. Bounded Context: AI Assistant ──────────────────────────────────
        $this->app->bind(AIEngineInterface::class, OpenAiEngineAdapter::class);
        $this->app->bind(CreditManagerInterface::class, EloquentCreditManagerAdapter::class);
        $this->app->bind(DraftStorageInterface::class, EloquentDraftStorageAdapter::class);

        // ── 3. Bounded Context: Billing & SaaS ────────────────────────────────
        $this->app->bind(PaymentGatewayInterface::class, WompiGatewayAdapter::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
