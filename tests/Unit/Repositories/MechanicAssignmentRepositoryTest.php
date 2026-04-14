<?php

namespace Tests\Unit\Repositories;

use App\Enums\MechanicAssignmentStatus;
use App\Models\MechanicAssignment;
use App\Models\User;
use App\Repositories\Eloquent\MechanicAssignmentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class MechanicAssignmentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MechanicAssignmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MechanicAssignmentRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | getPaginatedAssignments
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_assignments_returns_paginated_results(): void
    {
        // Create test data
        $mechanic = User::factory()->create();
        $workOrderService = \App\Models\WorkOrderService::factory()->create();

        MechanicAssignment::factory()->count(20)->create([
            'mechanic_id' => $mechanic->id,
            'work_order_service_id' => $workOrderService->id,
        ]);

        $result = $this->repository->getPaginatedAssignments();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    public function test_get_paginated_assignments_filters_by_status(): void
    {
        $mechanic = User::factory()->create();
        $workOrderService = \App\Models\WorkOrderService::factory()->create();

        MechanicAssignment::factory()->count(5)->create([
            'mechanic_id' => $mechanic->id,
            'work_order_service_id' => $workOrderService->id,
            'status' => MechanicAssignmentStatus::ASSIGNED,
        ]);

        MechanicAssignment::factory()->count(5)->create([
            'mechanic_id' => $mechanic->id,
            'work_order_service_id' => $workOrderService->id,
            'status' => MechanicAssignmentStatus::IN_PROGRESS,
        ]);

        $this->get('/api/v1/mechanic-assignments?filter[status]=assigned');

        $result = $this->repository->getPaginatedAssignments();

        foreach ($result as $assignment) {
            $this->assertEquals(MechanicAssignmentStatus::ASSIGNED, $assignment->status);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | findById
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id_returns_assignment(): void
    {
        $mechanic = User::factory()->create();
        $workOrderService = \App\Models\WorkOrderService::factory()->create();

        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $mechanic->id,
            'work_order_service_id' => $workOrderService->id,
        ]);

        $result = $this->repository->findById($assignment->id);

        $this->assertInstanceOf(MechanicAssignment::class, $result);
        $this->assertEquals($assignment->id, $result->id);
    }

    public function test_find_by_id_throws_exception_for_invalid_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById('non-existent-id');
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    public function test_create_creates_and_returns_assignment(): void
    {
        $mechanic = User::factory()->create();
        $workOrderService = \App\Models\WorkOrderService::factory()->create();

        $data = [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $mechanic->id,
            'status' => MechanicAssignmentStatus::ASSIGNED->value,
            'assigned_at' => now(),
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(MechanicAssignment::class, $result);
        $this->assertDatabaseHas('mechanic_assignments', [
            'id' => $result->id,
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $mechanic->id,
            'status' => MechanicAssignmentStatus::ASSIGNED->value,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_update_updates_and_returns_assignment(): void
    {
        $mechanic = User::factory()->create();
        $workOrderService = \App\Models\WorkOrderService::factory()->create();

        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $mechanic->id,
            'work_order_service_id' => $workOrderService->id,
            'status' => MechanicAssignmentStatus::ASSIGNED,
        ]);

        $data = [
            'status' => MechanicAssignmentStatus::IN_PROGRESS->value,
            'completed_at' => now(),
        ];

        $result = $this->repository->update($assignment, $data);

        $this->assertInstanceOf(MechanicAssignment::class, $result);
        $this->assertEquals(MechanicAssignmentStatus::IN_PROGRESS, $result->status);
        $this->assertNotNull($result->completed_at);
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    public function test_delete_deletes_assignment(): void
    {
        $mechanic = User::factory()->create();
        $workOrderService = \App\Models\WorkOrderService::factory()->create();

        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $mechanic->id,
            'work_order_service_id' => $workOrderService->id,
        ]);

        $this->repository->delete($assignment);

        $this->assertDatabaseMissing('mechanic_assignments', [
            'id' => $assignment->id,
        ]);
    }
}
