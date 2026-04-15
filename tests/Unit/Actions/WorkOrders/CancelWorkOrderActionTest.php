<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\CancelWorkOrderAction;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CancelWorkOrderActionTest extends TestCase
{
    protected MockInterface|WorkOrderRepositoryInterface $workOrderRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_cancel_draft_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::CANCELED->value)
            ->andReturn($workOrder);

        $action = new CancelWorkOrderAction($this->workOrderRepositoryMock);
        $result = $action->execute('wo-123');

        $this->assertSame($workOrder, $result);
    }

    public function test_cancel_diagnosed_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DIAGNOSED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::CANCELED->value)
            ->andReturn($workOrder);

        $action = new CancelWorkOrderAction($this->workOrderRepositoryMock);
        $result = $action->execute('wo-123');

        $this->assertSame($workOrder, $result);
    }

    public function test_cancel_approved_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::CANCELED->value)
            ->andReturn($workOrder);

        $action = new CancelWorkOrderAction($this->workOrderRepositoryMock);
        $result = $action->execute('wo-123');

        $this->assertSame($workOrder, $result);
    }

    public function test_cancel_throws_exception_when_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot cancel Work Orders that are currently in progress, completed, closed, or already canceled.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new CancelWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');
    }

    public function test_cancel_throws_exception_when_completed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot cancel Work Orders that are currently in progress, completed, closed, or already canceled.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::COMPLETED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new CancelWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');
    }

    public function test_cancel_throws_exception_when_already_canceled(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot cancel Work Orders that are currently in progress, completed, closed, or already canceled.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::CANCELED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new CancelWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
