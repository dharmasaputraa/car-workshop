<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\AssignMechanicToServiceAction;
use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\MechanicAssigned;
use App\Models\MechanicAssignment;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AssignMechanicToServiceActionTest extends TestCase
{
    protected MockInterface|WorkOrderRepositoryInterface $workOrderRepositoryMock;
    protected MockInterface|WorkOrderServiceRepositoryInterface $workOrderServiceRepositoryMock;
    protected MockInterface|MechanicAssignmentRepositoryInterface $mechanicAssignmentRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
        $this->workOrderServiceRepositoryMock = Mockery::mock(WorkOrderServiceRepositoryInterface::class);
        $this->mechanicAssignmentRepositoryMock = Mockery::mock(MechanicAssignmentRepositoryInterface::class);
    }

    public function test_assign_mechanic_to_service_in_approved_work_order(): void
    {
        Event::fake();

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->shouldReceive('setAttribute')->passthru();
        $woService->workOrder = $workOrder;

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->shouldReceive('loadMissing')->andReturnSelf();

        /** @var HasMany|MockInterface $hasManyMock */
        $hasManyMock = Mockery::mock(HasMany::class);

        $hasManyMock->shouldReceive('where')->with('mechanic_id', 'mech-123')->andReturnSelf();
        $hasManyMock->shouldReceive('where')->with('status', '!=', MechanicAssignmentStatus::CANCELED->value)->andReturnSelf();
        $hasManyMock->shouldReceive('first')->andReturnNull();

        $woService->shouldReceive('mechanicAssignments')->andReturn($hasManyMock);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return is_array($data) &&
                    $data['work_order_service_id'] === 'wos-123' &&
                    $data['mechanic_id'] === 'mech-123' &&
                    $data['status'] === MechanicAssignmentStatus::ASSIGNED->value &&
                    isset($data['assigned_at']);
            }))
            ->andReturn($assignment);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($woService, ServiceItemStatus::ASSIGNED->value)
            ->andReturn($woService);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::IN_PROGRESS->value)
            ->andReturn($workOrder);

        $action = new AssignMechanicToServiceAction(
            $this->workOrderRepositoryMock,
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $result = $action->execute('wos-123', ['mech-123']);

        $this->assertCount(1, $result);
        $this->assertSame($assignment, $result->first());
        Event::assertDispatched(MechanicAssigned::class);
    }

    public function test_assign_mechanic_to_service_in_in_progress_work_order(): void
    {
        Event::fake();

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->shouldReceive('setAttribute')->passthru();
        $woService->workOrder = $workOrder;

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->shouldReceive('loadMissing')->andReturnSelf();

        /** @var HasMany|MockInterface $hasManyMock */
        $hasManyMock = Mockery::mock(HasMany::class);

        $hasManyMock->shouldReceive('where')->with('mechanic_id', 'mech-123')->andReturnSelf();
        $hasManyMock->shouldReceive('where')->with('status', '!=', MechanicAssignmentStatus::CANCELED->value)->andReturnSelf();
        $hasManyMock->shouldReceive('first')->andReturnNull();

        $woService->shouldReceive('mechanicAssignments')->andReturn($hasManyMock);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($assignment);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->andReturn($woService);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->never();

        $action = new AssignMechanicToServiceAction(
            $this->workOrderRepositoryMock,
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $result = $action->execute('wos-123', ['mech-123']);

        $this->assertCount(1, $result);
        $this->assertSame($assignment, $result->first());
    }

    public function test_assign_throws_exception_when_work_order_not_approved(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mechanics can only be assigned to Work Orders that are Approved or In Progress.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->workOrder = $workOrder;

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $action = new AssignMechanicToServiceAction(
            $this->workOrderRepositoryMock,
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $action->execute('wos-123', ['mech-123']);
    }

    public function test_assign_skips_mechanic_already_assigned(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;

        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->workOrder = $workOrder;

        /** @var MechanicAssignment|MockInterface $existingAssignment */
        $existingAssignment = Mockery::mock(MechanicAssignment::class)->makePartial();

        /** @var HasMany|MockInterface $hasManyMock */
        $hasManyMock = Mockery::mock(HasMany::class);

        $hasManyMock->shouldReceive('where')->with('mechanic_id', 'mech-123')->andReturnSelf();
        $hasManyMock->shouldReceive('where')->with('status', '!=', MechanicAssignmentStatus::CANCELED->value)->andReturnSelf();
        $hasManyMock->shouldReceive('first')->andReturn($existingAssignment);

        $woService->shouldReceive('mechanicAssignments')->andReturn($hasManyMock);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wos-123')
            ->andReturn($woService);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('create')
            ->never();

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->never();

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->never();

        $action = new AssignMechanicToServiceAction(
            $this->workOrderRepositoryMock,
            $this->workOrderServiceRepositoryMock,
            $this->mechanicAssignmentRepositoryMock
        );

        $result = $action->execute('wos-123', ['mech-123']);

        $this->assertCount(0, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
