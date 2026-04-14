<?php

namespace Tests\Unit\Actions;

use App\Actions\WorkOrders\AssignMechanicToServiceAction;
use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\MechanicAssigned;
use App\Models\MechanicAssignment;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssignMechanicToServiceActionTest extends TestCase
{
    use RefreshDatabase;

    private AssignMechanicToServiceAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(AssignMechanicToServiceAction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Successfully Assigns Mechanic to APPROVED Work Order
    |--------------------------------------------------------------------------
    */

    public function test_assigns_mechanic_to_approved_work_order(): void
    {
        Event::fake();

        // Create work order in APPROVED status
        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::APPROVED,
        ]);

        $workOrderService = WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
            'status' => ServiceItemStatus::PENDING,
        ]);

        $mechanic = \App\Models\User::factory()->create();

        // Execute action
        $result = $this->action->execute($workOrderService->id, $mechanic->id);

        // Assert assignment created
        $this->assertInstanceOf(MechanicAssignment::class, $result);
        $this->assertEquals($workOrderService->id, $result->work_order_service_id);
        $this->assertEquals($mechanic->id, $result->mechanic_id);
        $this->assertEquals(MechanicAssignmentStatus::ASSIGNED, $result->status);
        $this->assertNotNull($result->assigned_at);

        // Assert WO Service status updated to in_progress
        $this->assertDatabaseHas('work_order_services', [
            'id' => $workOrderService->id,
            'status' => 'in_progress',
        ]);

        // Assert Work Order status updated to IN_PROGRESS
        $workOrder->refresh();
        $this->assertEquals(WorkOrderStatus::IN_PROGRESS, $workOrder->status);

        // Assert event dispatched
        Event::assertDispatched(MechanicAssigned::class, function ($event) use ($result) {
            return $event->assignment->id === $result->id;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Successfully Assigns Mechanic to IN_PROGRESS Work Order
    |--------------------------------------------------------------------------
    */

    public function test_assigns_mechanic_to_in_progress_work_order(): void
    {
        Event::fake();

        // Create work order already in IN_PROGRESS status
        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::IN_PROGRESS,
        ]);

        $workOrderService = WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
            'status' => ServiceItemStatus::PENDING,
        ]);

        $mechanic = \App\Models\User::factory()->create();

        // Execute action
        $result = $this->action->execute($workOrderService->id, $mechanic->id);

        // Assert assignment created
        $this->assertInstanceOf(MechanicAssignment::class, $result);
        $this->assertEquals($workOrderService->id, $result->work_order_service_id);
        $this->assertEquals($mechanic->id, $result->mechanic_id);

        // Assert WO Service status updated to in_progress
        $this->assertDatabaseHas('work_order_services', [
            'id' => $workOrderService->id,
            'status' => 'in_progress',
        ]);

        // Assert Work Order status REMAINS IN_PROGRESS (not changed)
        $workOrder->refresh();
        $this->assertEquals(WorkOrderStatus::IN_PROGRESS, $workOrder->status);

        // Assert event dispatched
        Event::assertDispatched(MechanicAssigned::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Throws Exception for Invalid Work Order Status
    |--------------------------------------------------------------------------
    */

    public function test_throws_exception_when_work_order_status_not_approved_or_in_progress(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Mechanics can only be assigned to Work Orders that are Approved or In Progress.");

        // Create work order in PENDING status (invalid for assignment)
        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::PENDING,
        ]);

        $workOrderService = WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
        ]);

        $mechanic = \App\Models\User::factory()->create();

        // Execute action - should throw exception
        $this->action->execute($workOrderService->id, $mechanic->id);
    }

    public function test_throws_exception_when_work_order_status_completed(): void
    {
        $this->expectException(\Exception::class);

        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::COMPLETED,
        ]);

        $workOrderService = WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
        ]);

        $mechanic = \App\Models\User::factory()->create();

        $this->action->execute($workOrderService->id, $mechanic->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Rollback on Failure
    |--------------------------------------------------------------------------
    */

    public function test_transaction_rolls_back_on_failure(): void
    {
        Event::fake();

        // Create work order with invalid status
        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::PENDING,
        ]);

        $workOrderService = WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
        ]);

        $mechanic = \App\Models\User::factory()->create();

        try {
            $this->action->execute($workOrderService->id, $mechanic->id);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert no assignment was created (transaction rolled back)
        $this->assertDatabaseMissing('mechanic_assignments', [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $mechanic->id,
        ]);

        // Assert WO Service status was not updated
        $workOrderService->refresh();
        $this->assertNotEquals('in_progress', $workOrderService->status);

        // Assert no event was dispatched
        Event::assertNotDispatched(MechanicAssigned::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Event Includes Loaded Relationships
    |--------------------------------------------------------------------------
    */

    public function test_event_dispatches_with_loaded_relationships(): void
    {
        Event::fake();

        $workOrder = WorkOrder::factory()
            ->for(\App\Models\Car::factory())
            ->create([
                'status' => WorkOrderStatus::APPROVED,
            ]);

        $service = \App\Models\Service::factory()->create();

        $workOrderService = WorkOrderService::factory()->create([
            'work_order_id' => $workOrder->id,
            'service_id' => $service->id,
        ]);

        $mechanic = \App\Models\User::factory()->create();

        $result = $this->action->execute($workOrderService->id, $mechanic->id);

        // Assert relationships are loaded on the event's assignment
        Event::assertDispatched(MechanicAssigned::class, function ($event) use ($mechanic) {
            $assignment = $event->assignment;

            return $assignment->relationLoaded('mechanic') &&
                $assignment->relationLoaded('workOrderService') &&
                $assignment->workOrderService->relationLoaded('service') &&
                $assignment->workOrderService->relationLoaded('workOrder') &&
                $assignment->workOrderService->workOrder->relationLoaded('car');
        });
    }
}
