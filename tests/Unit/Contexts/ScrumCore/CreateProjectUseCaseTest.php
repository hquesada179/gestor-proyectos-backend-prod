<?php

namespace Tests\Unit\Contexts\ScrumCore;

use App\Contexts\ScrumCore\Application\DTOs\CreateProjectDTO;
use App\Contexts\ScrumCore\Application\UseCases\CreateProjectUseCase;
use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectAuditLoggerInterface;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateProjectUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_and_audits_project(): void
    {
        $userId = 10;
        $dto = new CreateProjectDTO(
            userId: $userId,
            nombre: 'Nuevo Scrum Project',
            descripcion: 'Descripcion de prueba',
            estado: 'activo',
            fechaInicio: '2026-11-01',
            fechaFinEstimada: '2026-12-01'
        );

        $createdProject = new ProjectEntity(
            id: 99,
            userId: $userId,
            nombre: $dto->nombre,
            descripcion: $dto->descripcion,
            estado: $dto->estado
        );

        $repoMock = Mockery::mock(ProjectRepositoryInterface::class);
        $repoMock->shouldReceive('create')
            ->once()
            ->with($userId, Mockery::on(function ($data) use ($dto) {
                return $data['nombre'] === $dto->nombre && $data['estado'] === $dto->estado;
            }))
            ->andReturn($createdProject);

        $auditMock = Mockery::mock(ProjectAuditLoggerInterface::class);
        $auditMock->shouldReceive('logProjectAction')
            ->once()
            ->with(99, 'created', 'Proyecto creado: «Nuevo Scrum Project»');

        $useCase = new CreateProjectUseCase($repoMock, $auditMock);
        $result = $useCase->execute($dto);

        $this->assertInstanceOf(ProjectEntity::class, $result);
        $this->assertEquals('Nuevo Scrum Project', $result->nombre);
    }
}
