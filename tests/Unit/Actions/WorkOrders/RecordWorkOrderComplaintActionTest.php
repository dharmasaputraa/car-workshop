<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\RecordWorkOrderComplaintAction;
use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderComplained;
use App\Models\Complaint;
use App\Models\WorkOrder;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintServiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RecordWorkOrderComplaintActionTest extends TestCase
{
    protected MockInterface|WorkOrderRepositoryInterface $workOrderRepositoryMock;
    protected MockInterface|ComplaintRepositoryInterface $complaintRepositoryMock;
    protected MockInterface|ComplaintServiceRepositoryInterface $complaintServiceRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
        $this->complaintRepositoryMock = Mockery::mock(ComplaintRepositoryInterface::class);
        $this->complaintServiceRepositoryMock = Mockery::mock(ComplaintServiceRepositoryInterface::class);
    }

    public function test_record_complaint_successfully(): void
    {
        Event::fake();

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::COMPLETED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        /** @var Complaint|MockInterface $complaint */
        $complaint = Mockery::mock(Complaint::class)->makePartial();
        $complaint->shouldReceive('setAttribute')->passthru();

        $servicesData = [
            ['service_id' => 'service-1', 'description' => 'Issue with brakes'],
        ];

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->complaintRepositoryMock
            ->shouldReceive('findByWorkOrderId')
            ->once()
            ->with('wo-123')
            ->andReturnNull();

        $this->complaintRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'work_order_id' => 'wo-123',
                'description' => 'Customer complaint',
                'status' => ComplaintStatus::PENDING->value,
            ])
            ->andReturn($complaint);

        $this->complaintServiceRepositoryMock
            ->shouldReceive('addServicesToComplaint')
            ->once()
            ->with($complaint, $servicesData);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::COMPLAINED->value)
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($workOrder, Mockery::type('array'))
            ->andReturn($workOrder);

        $action = new RecordWorkOrderComplaintAction(
            $this->workOrderRepositoryMock,
            $this->complaintRepositoryMock,
            $this->complaintServiceRepositoryMock
        );

        $result = $action->execute('wo-123', 'Customer complaint', $servicesData);

        $this->assertSame($workOrder, $result);
        Event::assertDispatched(WorkOrderComplained::class);
    }

    public function test_record_throws_exception_when_not_completed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only completed work orders can have complaints recorded.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new RecordWorkOrderComplaintAction(
            $this->workOrderRepositoryMock,
            $this->complaintRepositoryMock,
            $this->complaintServiceRepositoryMock
        );

        $action->execute('wo-123', 'Customer complaint', []);
    }

    public function test_record_throws_exception_when_complaint_exists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('A complaint already exists for this work order.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::COMPLETED->value;

        /** @var Complaint|MockInterface $existingComplaint */
        $existingComplaint = Mockery::mock(Complaint::class);

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->complaintRepositoryMock
            ->shouldReceive('findByWorkOrderId')
            ->once()
            ->with('wo-123')
            ->andReturn($existingComplaint);

        $action = new RecordWorkOrderComplaintAction(
            $this->workOrderRepositoryMock,
            $this->complaintRepositoryMock,
            $this->complaintServiceRepositoryMock
        );

        $action->execute('wo-123', 'Customer complaint', []);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
