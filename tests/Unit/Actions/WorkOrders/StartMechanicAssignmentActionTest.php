<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\StartMechanicAssignmentAction;
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

class StartMechanicAssignmentActionTest extends TestCase
{
    protected MockInterface|MechanicAssignmentRepositoryInterface $mechanicAssignmentRepositoryMock;
    protected MockInterface|WorkOrderServiceRepositoryInterface $workOrderServiceRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mechanicAssignmentRepositoryMock = Mockery::mock(MechanicAssignmentRepositoryInterface::class);
        $this->workOrderServiceRepositoryMock = Mockery::mock(WorkOrderServiceRepositoryInterface::class);
    }

    public function test_start_assignment_successfully(): void
    {
        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::ASSIGNED->value;
        $woService->shouldReceive('setAttribute')->passthru();

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::ASSIGNED->value;
        $assignment->shouldReceive('loadMissing')->andReturnSelf();
        $assignment->shouldReceive('setAttribute')->passthru();
        $assignment->workOrderService = $woService;

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($assignment, ['status' => MechanicAssignmentStatus::IN_PROGRESS->value])
            ->andReturn($assignment);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($woService, ServiceItemStatus::IN_PROGRESS->value)
            ->andReturn($woService);

        $action = new StartMechanicAssignmentAction(
            $this->mechanicAssignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('assignment-123');

        $this->assertSame($assignment, $result);
    }

    public function test_start_assignment_when_service_already_in_progress(): void
    {
        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::IN_PROGRESS->value;

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::ASSIGNED->value;
        $assignment->shouldReceive('loadMissing')->andReturnSelf();
        $assignment->shouldReceive('setAttribute')->passthru();
        $assignment->workOrderService = $woService;

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn($assignment);

        // Service status should NOT be updated since it's already IN_PROGRESS
        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->never();

        $action = new StartMechanicAssignmentAction(
            $this->mechanicAssignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('assignment-123');

        $this->assertSame($assignment, $result);
    }

    public function test_start_throws_exception_when_not_assigned(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Assignment must be in ASSIGNED status to start.');

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::IN_PROGRESS->value;

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $action = new StartMechanicAssignmentAction(
            $this->mechanicAssignmentRepositoryMock,
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
