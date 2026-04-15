<?php

namespace Tests\Unit\Repositories;

use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Eloquent\WorkOrderServiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkOrderServiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderServiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new WorkOrderServiceRepository(app(\App\Repositories\Contracts\ServiceRepositoryInterface::class));
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id(): void
    {
        $woService = WorkOrderService::factory()->create();

        $result = $this->repository->findById($woService->id);

        $this->assertInstanceOf(WorkOrderService::class, $result);
        $this->assertEquals($woService->id, $result->id);
        $this->assertEquals($woService->price, $result->price);
        $this->assertEquals($woService->status, $result->status);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_add_services_to_work_order_auto_fetches_price_and_sets_pending(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $service = Service::factory()->create(['base_price' => 150.00]);

        $servicesData = [
            [
                'service_id' => $service->id,
                'notes' => 'Oil change required',
            ],
        ];

        $this->repository->addServicesToWorkOrder($workOrder, $servicesData);

        $this->assertDatabaseHas('work_order_services', [
            'work_order_id' => $workOrder->id,
            'service_id' => $service->id,
            'price' => 150.00,
            'status' => 'pending',
            'notes' => 'Oil change required',
        ]);
    }

    public function test_cancel_all_services_sets_canceled_status(): void
    {
        $workOrder = WorkOrder::factory()->create();

        // Create some services with different statuses
        WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
            'status' => \App\Enums\ServiceItemStatus::PENDING->value,
        ]);

        WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
            'status' => \App\Enums\ServiceItemStatus::ASSIGNED->value,
        ]);

        // One already canceled should not be affected
        WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
            'status' => \App\Enums\ServiceItemStatus::CANCELED->value,
        ]);

        $this->repository->cancelAllServices($workOrder);

        // All should be canceled now
        $services = WorkOrderService::where('work_order_id', $workOrder->id)->get();
        $this->assertCount(3, $services);
        foreach ($services as $service) {
            $this->assertEquals(\App\Enums\ServiceItemStatus::CANCELED->value, $service->status->value);
        }
    }

    public function test_update_status(): void
    {
        $woService = WorkOrderService::factory()->create(['status' => \App\Enums\ServiceItemStatus::PENDING->value]);

        $result = $this->repository->updateStatus($woService, \App\Enums\ServiceItemStatus::IN_PROGRESS->value);

        $this->assertInstanceOf(WorkOrderService::class, $result);
        $this->assertEquals(\App\Enums\ServiceItemStatus::IN_PROGRESS->value, $result->status->value);
        $this->assertDatabaseHas('work_order_services', [
            'id' => $woService->id,
            'status' => \App\Enums\ServiceItemStatus::IN_PROGRESS->value,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGNMENT CHECKS
    |--------------------------------------------------------------------------
    */

    public function test_has_active_assignments(): void
    {
        $woService = WorkOrderService::factory()->create();
        $mechanic = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\RoleType::MECHANIC->value, 'guard_name' => 'api']);
        $mechanic->assignRole(\App\Enums\RoleType::MECHANIC->value);

        // Create an active assignment
        \App\Models\MechanicAssignment::factory()->create([
            'work_order_service_id' => $woService->id,
            'mechanic_id' => $mechanic->id,
            'status' => 'assigned',
        ]);

        $result = $this->repository->hasActiveAssignments($woService);

        $this->assertTrue($result);
    }

    public function test_has_no_active_assignments_when_all_canceled(): void
    {
        $woService = WorkOrderService::factory()->create();
        $mechanic = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\RoleType::MECHANIC->value, 'guard_name' => 'api']);
        $mechanic->assignRole(\App\Enums\RoleType::MECHANIC->value);

        // Create a canceled assignment
        \App\Models\MechanicAssignment::factory()->create([
            'work_order_service_id' => $woService->id,
            'mechanic_id' => $mechanic->id,
            'status' => 'canceled',
        ]);

        $result = $this->repository->hasActiveAssignments($woService);

        $this->assertFalse($result);
    }

    public function test_has_no_active_assignments_when_none_exist(): void
    {
        $woService = WorkOrderService::factory()->create();

        $result = $this->repository->hasActiveAssignments($woService);

        $this->assertFalse($result);
    }

    public function test_has_uncompleted_assignments(): void
    {
        $woService = WorkOrderService::factory()->create();
        $mechanic = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\RoleType::MECHANIC->value, 'guard_name' => 'api']);
        $mechanic->assignRole(\App\Enums\RoleType::MECHANIC->value);

        // Create an in-progress assignment
        \App\Models\MechanicAssignment::factory()->create([
            'work_order_service_id' => $woService->id,
            'mechanic_id' => $mechanic->id,
            'status' => 'in_progress',
        ]);

        $result = $this->repository->hasUncompletedAssignments($woService);

        $this->assertTrue($result);
    }

    public function test_has_no_uncompleted_assignments_when_all_completed(): void
    {
        $woService = WorkOrderService::factory()->create();
        $mechanic = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\RoleType::MECHANIC->value, 'guard_name' => 'api']);
        $mechanic->assignRole(\App\Enums\RoleType::MECHANIC->value);

        // Create a completed assignment
        \App\Models\MechanicAssignment::factory()->create([
            'work_order_service_id' => $woService->id,
            'mechanic_id' => $mechanic->id,
            'status' => 'completed',
        ]);

        $result = $this->repository->hasUncompletedAssignments($woService);

        $this->assertFalse($result);
    }
}
