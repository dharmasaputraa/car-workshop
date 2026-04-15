<?php

namespace Tests\Unit\Actions\Complaints;

use App\Actions\Complaints\RejectComplaintAction;
use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Complaint;
use App\Models\WorkOrder;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RejectComplaintActionTest extends TestCase
{

    /** @var MockInterface|ComplaintRepositoryInterface */
    protected $complaintRepositoryMock;

    /** @var MockInterface|WorkOrderRepositoryInterface */
    protected $workOrderRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->complaintRepositoryMock = Mockery::mock(ComplaintRepositoryInterface::class);
        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_reject_pending_complaint_successfully(): void
    {
        /** @var Complaint|MockInterface $complaint */
        $complaint = Mockery::mock(Complaint::class)->makePartial();
        $complaint->id = 'complaint-123';
        $complaint->work_order_id = 'work-order-123';
        $complaint->status = ComplaintStatus::PENDING;
        $complaint->shouldReceive('setAttribute')->passthru();

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->id = 'work-order-123';
        $workOrder->status = WorkOrderStatus::COMPLAINED;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->complaintRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->id)
            ->andReturn($complaint);

        $this->complaintRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($complaint, ComplaintStatus::REJECTED->value)
            ->andReturn($complaint);

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->work_order_id)
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::COMPLETED->value)
            ->andReturn($workOrder);

        $this->complaintRepositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($complaint, ['workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service'])
            ->andReturn($complaint);

        $action = new RejectComplaintAction($this->complaintRepositoryMock, $this->workOrderRepositoryMock);
        $result = $action->execute($complaint->id);

        $this->assertSame($complaint, $result);
    }

    public function test_reject_in_progress_complaint_successfully(): void
    {
        /** @var Complaint|MockInterface $complaint */
        $complaint = Mockery::mock(Complaint::class)->makePartial();
        $complaint->id = 'complaint-123';
        $complaint->work_order_id = 'work-order-123';
        $complaint->status = ComplaintStatus::IN_PROGRESS;
        $complaint->shouldReceive('setAttribute')->passthru();

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->id = 'work-order-123';
        $workOrder->status = WorkOrderStatus::IN_PROGRESS;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->complaintRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->id)
            ->andReturn($complaint);

        $this->complaintRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($complaint, ComplaintStatus::REJECTED->value)
            ->andReturn($complaint);

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->work_order_id)
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::COMPLETED->value)
            ->andReturn($workOrder);

        $this->complaintRepositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($complaint, ['workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service'])
            ->andReturn($complaint);

        $action = new RejectComplaintAction($this->complaintRepositoryMock, $this->workOrderRepositoryMock);
        $result = $action->execute($complaint->id);

        $this->assertSame($complaint, $result);
    }

    public function test_reject_throws_exception_when_not_pending_or_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only pending or in-progress complaints can be rejected.');

        $complaint = Mockery::mock(Complaint::class)->makePartial();
        $complaint->id = 'complaint-123';
        $complaint->work_order_id = 'work-order-123';
        $complaint->status = ComplaintStatus::RESOLVED;
        $complaint->shouldReceive('setAttribute')->passthru();

        $this->complaintRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->id)
            ->andReturn($complaint);

        $action = new RejectComplaintAction($this->complaintRepositoryMock, $this->workOrderRepositoryMock);
        $action->execute($complaint->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
