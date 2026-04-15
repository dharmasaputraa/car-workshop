<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\CreateWorkOrderAction;
use App\DTOs\WorkOrder\CreateWorkOrderData;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateWorkOrderActionTest extends TestCase
{
    /** @var MockInterface|WorkOrderRepositoryInterface */
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_create_work_order_sets_draft_status_and_order_number(): void
    {
        $dto = new CreateWorkOrderData(
            carId: 'car-123',
            diagnosisNotes: 'Test diagnosis',
            estimatedCompletion: '2026-04-20'
        );

        $user = Mockery::mock(WorkOrder::class);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return isset($payload['order_number']) &&
                    $payload['status'] === 'draft' &&
                    $payload['created_by'] === 'user-123' &&
                    $payload['car_id'] === 'car-123';
            }))
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($user, ['car', 'creator'])
            ->andReturn($user);

        $action = new CreateWorkOrderAction($this->repositoryMock);
        $result = $action->execute($dto, 'user-123');

        $this->assertSame($user, $result);
    }

    public function test_create_work_order_returns_work_order_with_relations(): void
    {
        $dto = new CreateWorkOrderData(
            carId: 'car-123',
            diagnosisNotes: null,
            estimatedCompletion: null
        );

        $user = Mockery::mock(WorkOrder::class);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($user, ['car', 'creator'])
            ->andReturn($user);

        $action = new CreateWorkOrderAction($this->repositoryMock);
        $result = $action->execute($dto, 'user-123');

        $this->assertSame($user, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
