<?php

namespace Tests\Unit\Repositories;

use App\Models\Car;
use App\Models\User;
use App\Models\WorkOrder;
use App\Repositories\Eloquent\WorkOrderRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class WorkOrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new WorkOrderRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_work_orders(): void
    {
        WorkOrder::factory()->count(20)->create();

        $result = $this->repository->getPaginatedWorkOrders();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items()); // Default PER_PAGE = 15
        $this->assertEquals(20, $result->total());
    }

    public function test_get_paginated_work_orders_filters_by_status(): void
    {
        WorkOrder::factory()->count(10)->create(['status' => \App\Enums\WorkOrderStatus::DRAFT->value]);
        WorkOrder::factory()->count(5)->create(['status' => \App\Enums\WorkOrderStatus::DIAGNOSED->value]);

        request()->merge(['filter' => ['status' => 'draft']]);

        $result = $this->repository->getPaginatedWorkOrders();

        $this->assertCount(10, $result->items());
        foreach ($result->items() as $wo) {
            $this->assertEquals(\App\Enums\WorkOrderStatus::DRAFT->value, $wo->status->value);
        }
    }

    public function test_find_by_id(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $result = $this->repository->findById($workOrder->id);

        $this->assertInstanceOf(WorkOrder::class, $result);
        $this->assertEquals($workOrder->id, $result->id);
        $this->assertEquals($workOrder->order_number, $result->order_number);
    }

    public function test_find_by_id_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById('invalid-uuid');
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_work_order(): void
    {
        $car = Car::factory()->create();
        $user = User::factory()->create();

        $data = [
            'order_number' => 'WO-20260415-ABCDE',
            'car_id' => $car->id,
            'created_by' => $user->id,
            'status' => 'draft',
            'diagnosis_notes' => 'Test diagnosis',
            'estimated_completion' => '2026-04-20',
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(WorkOrder::class, $result);
        $this->assertDatabaseHas('work_orders', [
            'order_number' => 'WO-20260415-ABCDE',
            'car_id' => $car->id,
            'status' => 'draft',
        ]);
        $this->assertNotNull($result->id);
    }

    public function test_update_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create([
            'diagnosis_notes' => 'Original diagnosis',
            'estimated_completion' => '2026-04-20',
        ]);

        $result = $this->repository->update($workOrder, [
            'diagnosis_notes' => 'Updated diagnosis',
            'estimated_completion' => '2026-04-25',
        ]);

        $this->assertInstanceOf(WorkOrder::class, $result);
        $this->assertEquals('Updated diagnosis', $result->diagnosis_notes);
        $this->assertEquals('2026-04-25', $result->estimated_completion->format('Y-m-d'));
        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'diagnosis_notes' => 'Updated diagnosis',
        ]);
    }

    public function test_update_status(): void
    {
        $workOrder = WorkOrder::factory()->create(['status' => \App\Enums\WorkOrderStatus::DRAFT->value]);

        $result = $this->repository->updateStatus($workOrder, \App\Enums\WorkOrderStatus::DIAGNOSED->value);

        $this->assertInstanceOf(WorkOrder::class, $result);
        $this->assertEquals(\App\Enums\WorkOrderStatus::DIAGNOSED->value, $result->status->value);
        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => \App\Enums\WorkOrderStatus::DIAGNOSED->value,
        ]);
    }

    public function test_delete_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $workOrderId = $workOrder->id;

        $this->repository->delete($workOrder);

        // Since WorkOrder doesn't use SoftDeletes, we just verify it's deleted from database
        $this->assertDatabaseMissing('work_orders', [
            'id' => $workOrderId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function test_load_relations(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $result = $this->repository->loadRelations($workOrder, ['car', 'creator']);

        $this->assertInstanceOf(WorkOrder::class, $result);
        $this->assertTrue($result->relationLoaded('car'));
        $this->assertTrue($result->relationLoaded('creator'));
    }

    public function test_load_missing_relations(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $result = $this->repository->loadMissingRelations($workOrder, ['car', 'creator']);

        $this->assertInstanceOf(WorkOrder::class, $result);
        $this->assertTrue($result->relationLoaded('car'));
        $this->assertTrue($result->relationLoaded('creator'));
    }
}
