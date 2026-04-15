<?php

namespace Tests\Unit\Repositories;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\WorkOrder;
use App\Repositories\Eloquent\ComplaintRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ComplaintRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ComplaintRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ComplaintRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_complaints(): void
    {
        Complaint::factory()->count(20)->create();

        $result = $this->repository->getPaginated();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items()); // Default PER_PAGE = 15
        $this->assertEquals(20, $result->total());
    }

    public function test_get_paginated_complaints_filters_by_status(): void
    {
        Complaint::factory()->count(10)->create(['status' => ComplaintStatus::PENDING->value]);
        Complaint::factory()->count(5)->create(['status' => ComplaintStatus::IN_PROGRESS->value]);

        request()->merge(['filter' => ['status' => 'pending']]);

        $result = $this->repository->getPaginated();

        $this->assertCount(10, $result->items());
        foreach ($result->items() as $complaint) {
            $this->assertEquals(ComplaintStatus::PENDING->value, $complaint->status->value);
        }
    }

    public function test_find_by_id(): void
    {
        $complaint = Complaint::factory()->create();

        $result = $this->repository->findById($complaint->id);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals($complaint->id, $result->id);
        $this->assertEquals($complaint->description, $result->description);
    }

    public function test_find_by_id_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById('invalid-uuid');
    }

    public function test_find_by_work_order_id(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $complaint = Complaint::factory()->create(['work_order_id' => $workOrder->id]);

        $result = $this->repository->findByWorkOrderId($workOrder->id);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals($complaint->id, $result->id);
        $this->assertEquals($workOrder->id, $result->work_order_id);
    }

    public function test_find_by_work_order_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByWorkOrderId('non-existent-wo-id');

        $this->assertNull($result);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_complaint(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $data = [
            'work_order_id' => $workOrder->id,
            'description' => 'Car is making strange noise',
            'status' => ComplaintStatus::PENDING->value,
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertDatabaseHas('complaints', [
            'work_order_id' => $workOrder->id,
            'description' => 'Car is making strange noise',
            'status' => ComplaintStatus::PENDING->value,
        ]);
        $this->assertNotNull($result->id);
    }

    public function test_create_complaint_uses_default_pending_status(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $data = [
            'work_order_id' => $workOrder->id,
            'description' => 'Default status test',
        ];

        $result = $this->repository->create($data);

        $this->assertEquals(ComplaintStatus::PENDING->value, $result->status->value);
    }

    public function test_update_complaint(): void
    {
        $complaint = Complaint::factory()->create([
            'description' => 'Original description',
        ]);

        $result = $this->repository->update($complaint, [
            'description' => 'Updated description',
        ]);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals('Updated description', $result->description);
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_update_status_to_in_progress(): void
    {
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::PENDING->value]);

        $result = $this->repository->updateStatus($complaint, ComplaintStatus::IN_PROGRESS->value);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals(ComplaintStatus::IN_PROGRESS->value, $result->status->value);
        $this->assertNotNull($result->in_progress_at);
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => ComplaintStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_update_status_to_resolved(): void
    {
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::IN_PROGRESS->value]);

        $result = $this->repository->updateStatus($complaint, ComplaintStatus::RESOLVED->value);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals(ComplaintStatus::RESOLVED->value, $result->status->value);
        $this->assertNotNull($result->resolved_at);
    }

    public function test_update_status_to_rejected(): void
    {
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::IN_PROGRESS->value]);

        $result = $this->repository->updateStatus($complaint, ComplaintStatus::REJECTED->value);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals(ComplaintStatus::REJECTED->value, $result->status->value);
        $this->assertNotNull($result->rejected_at);
    }

    public function test_delete_complaint(): void
    {
        $complaint = Complaint::factory()->create();
        $complaintId = $complaint->id;

        $this->repository->delete($complaint);

        $this->assertDatabaseMissing('complaints', [
            'id' => $complaintId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function test_load_missing_relations(): void
    {
        $complaint = Complaint::factory()->create();

        $result = $this->repository->loadMissingRelations($complaint, ['workOrder', 'complaintServices']);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertTrue($result->relationLoaded('workOrder'));
        $this->assertTrue($result->relationLoaded('complaintServices'));
    }
}
