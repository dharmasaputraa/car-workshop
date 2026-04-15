<?php

namespace Tests\Unit\Actions\Complaints;

use App\Actions\Complaints\ResolveComplaintAction;
use App\Enums\ComplaintStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\ComplaintResolved;
use App\Models\Complaint;
use App\Models\ComplaintService;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintServiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ResolveComplaintActionTest extends TestCase
{

    /** @var MockInterface|ComplaintRepositoryInterface */
    protected $complaintRepositoryMock;

    /** @var MockInterface|ComplaintServiceRepositoryInterface */
    protected $complaintServiceRepositoryMock;

    /** @var MockInterface|WorkOrderRepositoryInterface */
    protected $workOrderRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->complaintRepositoryMock = Mockery::mock(ComplaintRepositoryInterface::class);
        $this->complaintServiceRepositoryMock = Mockery::mock(ComplaintServiceRepositoryInterface::class);
        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_resolve_complaint_when_all_services_completed(): void
    {
        Event::fake();

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

        $this->complaintServiceRepositoryMock
            ->shouldReceive('areAllServicesCompleted')
            ->once()
            ->with($complaint)
            ->andReturn(true);

        $this->complaintRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($complaint, ComplaintStatus::RESOLVED->value)
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

        $action = new ResolveComplaintAction(
            $this->complaintRepositoryMock,
            $this->complaintServiceRepositoryMock,
            $this->workOrderRepositoryMock
        );
        $result = $action->execute($complaint->id);

        $this->assertSame($complaint, $result);

        Event::assertDispatched(ComplaintResolved::class, function ($event) use ($complaint) {
            return $event->complaint === $complaint;
        });
    }

    public function test_resolve_throws_exception_when_not_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only in-progress complaints can be resolved.');

        /** @var Complaint|MockInterface $complaint */
        $complaint = Mockery::mock(Complaint::class)->makePartial();
        $complaint->id = 'complaint-123';
        $complaint->work_order_id = 'work-order-123';
        $complaint->status = ComplaintStatus::PENDING;
        $complaint->shouldReceive('setAttribute')->passthru();

        $this->complaintRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->id)
            ->andReturn($complaint);

        $action = new ResolveComplaintAction(
            $this->complaintRepositoryMock,
            $this->complaintServiceRepositoryMock,
            $this->workOrderRepositoryMock
        );
        $action->execute($complaint->id);
    }

    public function test_resolve_throws_exception_when_services_not_completed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('All complaint services must be completed before resolving the complaint.');

        /** @var Complaint|MockInterface $complaint */
        $complaint = Mockery::mock(Complaint::class)->makePartial();
        $complaint->id = 'complaint-123';
        $complaint->work_order_id = 'work-order-123';
        $complaint->status = ComplaintStatus::IN_PROGRESS;
        $complaint->shouldReceive('setAttribute')->passthru();

        $this->complaintRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($complaint->id)
            ->andReturn($complaint);

        $this->complaintServiceRepositoryMock
            ->shouldReceive('areAllServicesCompleted')
            ->once()
            ->with($complaint)
            ->andReturn(false);

        $action = new ResolveComplaintAction(
            $this->complaintRepositoryMock,
            $this->complaintServiceRepositoryMock,
            $this->workOrderRepositoryMock
        );
        $action->execute($complaint->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
