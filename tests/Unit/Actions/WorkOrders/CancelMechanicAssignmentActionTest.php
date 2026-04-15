<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\CancelMechanicAssignmentAction;
use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Models\MechanicAssignment;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CancelMechanicAssignmentActionTest extends TestCase
{
    protected MockInterface|MechanicAssignmentRepositoryInterface $assignmentRepositoryMock;
    protected MockInterface|WorkOrderServiceRepositoryInterface $workOrderServiceRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignmentRepositoryMock = Mockery::mock(MechanicAssignmentRepositoryInterface::class);
        $this->workOrderServiceRepositoryMock = Mockery::mock(WorkOrderServiceRepositoryInterface::class);
    }

    public function test_cancel_assignment_reverts_service_to_pending(): void
    {
        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->shouldReceive('setAttribute')->passthru();

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::ASSIGNED->value;
        $assignment->shouldReceive('setAttribute')->passthru();
        $assignment->workOrderService = $woService;

        $this->assignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $this->assignmentRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($assignment, ['status' => MechanicAssignmentStatus::CANCELED->value])
            ->andReturn($assignment);

        // No active assignments remaining
        $this->workOrderServiceRepositoryMock
            ->shouldReceive('hasActiveAssignments')
            ->once()
            ->with($woService)
            ->andReturn(false);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($woService, ServiceItemStatus::PENDING->value)
            ->andReturn($woService);

        $action = new CancelMechanicAssignmentAction(
            $this->assignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('assignment-123');

        $this->assertSame($assignment, $result);
    }

    public function test_cancel_assignment_keeps_service_status(): void
    {
        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::ASSIGNED->value;
        $assignment->shouldReceive('setAttribute')->passthru();
        $assignment->workOrderService = $woService;

        $this->assignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $this->assignmentRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn($assignment);

        // Other active assignments still exist
        $this->workOrderServiceRepositoryMock
            ->shouldReceive('hasActiveAssignments')
            ->once()
            ->with($woService)
            ->andReturn(true);

        // Service status should NOT be changed
        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->never();

        $action = new CancelMechanicAssignmentAction(
            $this->assignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('assignment-123');

        $this->assertSame($assignment, $result);
    }

    public function test_cancel_throws_exception_when_completed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot cancel Mechanic Assignments that have been completed or already canceled.');

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::COMPLETED->value;
        $assignment->workOrderService = Mockery::mock(WorkOrderService::class);

        $this->assignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $action = new CancelMechanicAssignmentAction(
            $this->assignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $action->execute('assignment-123');
    }

    public function test_cancel_throws_exception_when_already_canceled(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot cancel Mechanic Assignments that have been completed or already canceled.');

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::CANCELED->value;
        $assignment->workOrderService = Mockery::mock(WorkOrderService::class);

        $this->assignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $action = new CancelMechanicAssignmentAction(
            $this->assignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $action->execute('assignment-123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
