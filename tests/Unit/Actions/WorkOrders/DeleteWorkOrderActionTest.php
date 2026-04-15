<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\DeleteWorkOrderAction;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteWorkOrderActionTest extends TestCase
{
    protected MockInterface|WorkOrderRepositoryInterface $workOrderRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_delete_draft_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');

        // If no exception thrown, test passes
        $this->assertTrue(true);
    }

    public function test_delete_diagnosed_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DIAGNOSED->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');

        $this->assertTrue(true);
    }

    public function test_delete_approved_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');

        $this->assertTrue(true);
    }

    public function test_delete_canceled_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::CANCELED->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');

        $this->assertTrue(true);
    }

    public function test_delete_throws_exception_when_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete Work Orders that are currently being worked on or have been completed.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');
    }

    public function test_delete_throws_exception_when_completed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete Work Orders that are currently being worked on or have been completed.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::COMPLETED->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');
    }

    public function test_delete_throws_exception_when_closed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete Work Orders that are currently being worked on or have been completed.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::CLOSED->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new DeleteWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
