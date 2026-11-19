<?php

namespace Tests\Unit\Contexts\ScrumCore;

use App\Contexts\ScrumCore\Application\UseCases\ListUserProjectsUseCase;
use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListUserProjectsUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_accessible_projects_for_user(): void
    {
        $userId = 42;
        $mockProjects = [
            new ProjectEntity(id: 1, userId: $userId, nombre: 'Proyecto Alpha', descripcion: null, estado: 'activo'),
            new ProjectEntity(id: 2, userId: $userId, nombre: 'Proyecto Beta', descripcion: null, estado: 'activo'),
        ];

        $repoMock = Mockery::mock(ProjectRepositoryInterface::class);
        $repoMock->shouldReceive('listAccessibleByUser')
            ->once()
            ->with($userId)
            ->andReturn($mockProjects);

        $useCase = new ListUserProjectsUseCase($repoMock);
        $result = $useCase->execute($userId);

        $this->assertCount(2, $result);
        $this->assertEquals('Proyecto Alpha', $result[0]->nombre);
    }
}
