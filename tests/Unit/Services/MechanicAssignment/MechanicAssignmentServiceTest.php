<?php

namespace Tests\Unit\Services\MechanicAssignment;

use App\Actions\WorkOrders\AssignMechanicToServiceAction;
use App\Actions\WorkOrders\CancelMechanicAssignmentAction;
use App\DTOs\Mechanic\MechanicAssignmentData;
use App\Enums\MechanicAssignmentStatus;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Services\MechanicAssignment\MechanicAssignmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery\MockInterface;
use Tests\TestCase;

class MechanicAssignmentServiceTest extends TestCase
{
    /** @var MockInterface|MechanicAssignmentRepositoryInterface */
    protected $repositoryMock;

    /** @var MockInterface|AssignMechanicToServiceAction */
    protected $assignActionMock;

    /** @var MockInterface|CancelMechanicAssignmentAction */
    protected $cancelActionMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = \Mockery::mock(MechanicAssignmentRepositoryInterface::class);
        $this->assignActionMock = \Mockery::mock(AssignMechanicToServiceAction::class);
        $this->cancelActionMock = \Mockery::mock(CancelMechanicAssignmentAction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_assignments_delegates_to_repository(): void
    {
        $paginator = \Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $this->repositoryMock
            ->shouldReceive('getPaginatedAssignments')
            ->once()
            ->andReturn($paginator);

        $service = new MechanicAssignmentService(
            $this->repositoryMock,
            $this->assignActionMock,
            $this->cancelActionMock
        );
        $result = $service->getPaginatedAssignments();

        $this->assertSame($paginator, $result);
    }

    public function test_get_assignment_by_id_delegates_to_repository(): void
    {
        $assignment = \Mockery::mock(MechanicAssignment::class);
        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($assignment);

        $service = new MechanicAssignmentService(
            $this->repositoryMock,
            $this->assignActionMock,
            $this->cancelActionMock
        );
        $result = $service->getAssignmentById('test-id');

        $this->assertSame($assignment, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_assignment_delegates_to_assign_action(): void
    {
        $dto = new MechanicAssignmentData(
            work_order_service_id: 'wo-service-id',
            mechanic_id: 'mechanic-id',
            status: MechanicAssignmentStatus::ASSIGNED,
            assigned_at: now()->toDateTimeString(),
        );

        $assignment = \Mockery::mock(MechanicAssignment::class);

        $this->assignActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('wo-service-id', 'mechanic-id')
            ->andReturn($assignment);

        $service = new MechanicAssignmentService(
            $this->repositoryMock,
            $this->assignActionMock,
            $this->cancelActionMock
        );
        $result = $service->createAssignment($dto);

        $this->assertSame($assignment, $result);
    }

    public function test_update_assignment_delegates_to_repository(): void
    {
        $assignment = \Mockery::mock(MechanicAssignment::class);

        $data = [
            'status' => 'completed',
            'completed_at' => now()->toDateTimeString(),
        ];

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($assignment, $data)
            ->andReturn($assignment);

        $service = new MechanicAssignmentService(
            $this->repositoryMock,
            $this->assignActionMock,
            $this->cancelActionMock
        );
        $result = $service->updateAssignment($assignment, $data);

        $this->assertSame($assignment, $result);
    }

    public function test_delete_assignment_delegates_to_repository(): void
    {
        $assignment = \Mockery::mock(MechanicAssignment::class);

        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($assignment);

        $service = new MechanicAssignmentService(
            $this->repositoryMock,
            $this->assignActionMock,
            $this->cancelActionMock
        );
        $service->deleteAssignment($assignment);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_cancel_assignment_delegates_to_cancel_action(): void
    {
        $assignment = \Mockery::mock(MechanicAssignment::class);

        $this->cancelActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('assignment-id')
            ->andReturn($assignment);

        $service = new MechanicAssignmentService(
            $this->repositoryMock,
            $this->assignActionMock,
            $this->cancelActionMock
        );
        $result = $service->cancelAssignment('assignment-id');

        $this->assertSame($assignment, $result);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
