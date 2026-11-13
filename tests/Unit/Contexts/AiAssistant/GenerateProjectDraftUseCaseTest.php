<?php

namespace Tests\Unit\Contexts\AiAssistant;

use App\Contexts\AiAssistant\Application\DTOs\GenerateProjectDraftDTO;
use App\Contexts\AiAssistant\Application\UseCases\GenerateProjectDraftUseCase;
use App\Contexts\AiAssistant\Domain\Ports\AIEngineInterface;
use App\Contexts\AiAssistant\Domain\Ports\CreditManagerInterface;
use App\Contexts\AiAssistant\Domain\Ports\DraftStorageInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GenerateProjectDraftUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_when_user_has_insufficient_credits(): void
    {
        $aiMock = Mockery::mock(AIEngineInterface::class);
        $creditMock = Mockery::mock(CreditManagerInterface::class);
        $draftMock = Mockery::mock(DraftStorageInterface::class);

        $creditMock->shouldReceive('hasEnoughCredits')
            ->once()
            ->with(1, 1)
            ->andReturn(false);

        $useCase = new GenerateProjectDraftUseCase($aiMock, $creditMock, $draftMock);
        $dto = new GenerateProjectDraftDTO(userId: 1, projectId: 5, inputBrief: 'Crear app de delivery');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Créditos insuficientes');

        $useCase->execute($dto);
    }

    public function test_it_generates_draft_saves_it_and_deducts_credits(): void
    {
        $userId = 1;
        $projectId = 5;
        $dto = new GenerateProjectDraftDTO(userId: $userId, projectId: $projectId, inputBrief: 'Sistema de subastas');

        $aiMock = Mockery::mock(AIEngineInterface::class);
        $creditMock = Mockery::mock(CreditManagerInterface::class);
        $draftMock = Mockery::mock(DraftStorageInterface::class);

        $creditMock->shouldReceive('hasEnoughCredits')
            ->once()
            ->with($userId, 1)
            ->andReturn(true);

        $proposalData = [
            'requirements' => [['nombre' => 'Req 1']],
            'userStories' => [['titulo' => 'US 1']],
        ];

        $aiMock->shouldReceive('generate')
            ->once()
            ->andReturn($proposalData);

        $draftMock->shouldReceive('saveDraft')
            ->once()
            ->with($projectId, $userId, $proposalData)
            ->andReturn(123);

        $creditMock->shouldReceive('deductCredits')
            ->once()
            ->with($userId, 1, 'generate_project_draft')
            ->andReturn(true);

        $useCase = new GenerateProjectDraftUseCase($aiMock, $creditMock, $draftMock);
        $result = $useCase->execute($dto);

        $this->assertEquals(123, $result['draft_id']);
        $this->assertEquals('generated', $result['status']);
        $this->assertEquals($proposalData, $result['proposal']);
    }
}
