<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\CompleteMechanicAssignmentAction;
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

class CompleteMechanicAssignmentActionTest extends TestCase
{
    protected MockInterface|MechanicAssignmentRepositoryInterface $mechanicAssignmentRepositoryMock;
    protected MockInterface|WorkOrderServiceRepositoryInterface $workOrderServiceRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mechanicAssignmentRepositoryMock = Mockery::mock(MechanicAssignmentRepositoryInterface::class);
        $this->workOrderServiceRepositoryMock = Mockery::mock(WorkOrderServiceRepositoryInterface::class);
    }

    public function test_complete_assignment_with_auto_service_completion(): void
    {
        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::IN_PROGRESS->value;
        $woService->shouldReceive('setAttribute')->passthru();
        $woService->shouldReceive('refresh')->andReturnSelf();

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::IN_PROGRESS->value;
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
            ->with(Mockery::on(function ($assignmentArg) use ($assignment) {
                return $assignmentArg === $assignment;
            }), Mockery::on(function ($data) {
                return is_array($data) &&
                    $data['status'] === MechanicAssignmentStatus::COMPLETED->value &&
                    isset($data['completed_at']);
            }))
            ->andReturn($assignment);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('hasUncompletedAssignments')
            ->once()
            ->with($woService)
            ->andReturn(false);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($woService, ServiceItemStatus::COMPLETED->value)
            ->andReturn($woService);

        $action = new CompleteMechanicAssignmentAction(
            $this->mechanicAssignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('assignment-123');

        $this->assertIsArray($result);
        $this->assertSame($assignment, $result['assignment']);
        $this->assertTrue($result['serviceAutoCompleted']);
        $this->assertSame($woService, $result['workOrderService']);
    }

    public function test_complete_assignment_without_auto_service_completion(): void
    {
        /** @var WorkOrderService|MockInterface $woService */
        $woService = Mockery::mock(WorkOrderService::class)->makePartial();
        $woService->status = ServiceItemStatus::IN_PROGRESS->value;
        $woService->shouldReceive('setAttribute')->passthru();
        $woService->shouldReceive('refresh')->andReturnSelf();

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::IN_PROGRESS->value;
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

        // Other mechanics still working
        $this->workOrderServiceRepositoryMock
            ->shouldReceive('hasUncompletedAssignments')
            ->once()
            ->with($woService)
            ->andReturn(true);

        // Service should NOT be auto-completed
        $this->workOrderServiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->never();

        $action = new CompleteMechanicAssignmentAction(
            $this->mechanicAssignmentRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('assignment-123');

        $this->assertIsArray($result);
        $this->assertSame($assignment, $result['assignment']);
        $this->assertFalse($result['serviceAutoCompleted']);
    }

    public function test_complete_throws_exception_when_not_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Assignment must be in IN_PROGRESS status to complete.');

        /** @var MechanicAssignment|MockInterface $assignment */
        $assignment = Mockery::mock(MechanicAssignment::class)->makePartial();
        $assignment->status = MechanicAssignmentStatus::ASSIGNED->value;
        $assignment->workOrderService = Mockery::mock(WorkOrderService::class);

        $this->mechanicAssignmentRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('assignment-123')
            ->andReturn($assignment);

        $action = new CompleteMechanicAssignmentAction(
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
