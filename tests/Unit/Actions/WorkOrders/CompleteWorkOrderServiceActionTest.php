<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\CompleteWorkOrderServiceAction;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CompleteWorkOrderServiceActionTest extends TestCase
{
    protected MockInterface|WorkOrderServiceRepositoryInterface $workOrderServiceRepositoryMock;
    protected MockInterface|MechanicAssignmentRepositoryInterface $mechanicAssignmentRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workOrderServiceRepositoryMock = Mockery::mock(WorkOrderServiceRepositoryInterface::class);
        $this->mechanicAssignmentRepositoryMock = Mockery::mock(MechanicAssignmentRepositoryInterface::class);
    }

    public function test_complete_service_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::IN_PROGRESS->value;
        $woService->shouldReceive('setAttribute')->passthru();
        $woService->shouldReceive('loadMissing')->andReturnSelf();
        $woService->workOrder = $workOrder;

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($woService, ServiceItemStatus::COMPLETED->value)
            ->andReturn($woService);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('completeByWorkOrderService')
            ->once()
            ->with('wos-123')
            ->andReturn(2);

        $action = new CompleteWorkOrderServiceAction(
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $result = $action->execute('wos-123');

        $this->assertSame($woService, $result);
    }

    public function test_complete_throws_exception_when_service_not_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Work Order Service must be in IN_PROGRESS status to complete.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::ASSIGNED->value;
        $woService->workOrder = $workOrder;

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $action = new CompleteWorkOrderServiceAction(
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $action->execute('wos-123');
    }

    public function test_complete_throws_exception_when_work_order_not_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Work Order must be IN_PROGRESS to complete a service.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::IN_PROGRESS->value;
        $woService->workOrder = $workOrder;

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $action = new CompleteWorkOrderServiceAction(
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $action->execute('wos-123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
