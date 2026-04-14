<?php

namespace Tests\Unit\Repositories;

use App\Models\Service;
use App\Repositories\Eloquent\ServiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ServiceRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_services(): void
    {
        Service::factory()->count(20)->create();

        $result = $this->repository->getPaginatedServices();

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items()); // Default per_page = 15
        $this->assertEquals(20, $result->total());
    }

    public function test_get_paginated_services_filters_by_name(): void
    {
        Service::factory()->create(['name' => 'Oil Change Service']);
        Service::factory()->create(['name' => 'Brake Inspection']);
        Service::factory()->create(['name' => 'Tire Rotation']);

        // Simulate request filter
        request()->merge(['filter' => ['name' => 'Oil']]);

        $result = $this->repository->getPaginatedServices();

        $this->assertCount(1, $result->items());
        $this->assertStringContainsString('Oil', $result->items()[0]->name);
    }

    public function test_get_paginated_services_filters_by_is_active(): void
    {
        Service::factory()->count(5)->create(['is_active' => true]);
        Service::factory()->count(3)->create(['is_active' => false]);

        // Simulate request filter
        request()->merge(['filter' => ['is_active' => true]]);

        $result = $this->repository->getPaginatedServices();

        $this->assertCount(5, $result->items());
        $this->assertTrue($result->items()[0]->is_active);
    }

    public function test_find_by_id(): void
    {
        $service = Service::factory()->create(['name' => 'Test Service']);

        $result = $this->repository->findById($service->id);

        $this->assertInstanceOf(Service::class, $result);
        $this->assertEquals($service->id, $result->id);
        $this->assertEquals('Test Service', $result->name);
    }

    public function test_find_by_id_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById('non-existent-id');
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create(): void
    {
        $data = [
            'name' => 'New Service',
            'description' => 'A new service description',
            'base_price' => 150000.50,
            'is_active' => true,
        ];

        $service = $this->repository->create($data);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertDatabaseHas('services', [
            'name' => 'New Service',
            'description' => 'A new service description',
            'base_price' => 150000.50,
            'is_active' => true,
        ]);
    }

    public function test_create_with_default_is_active(): void
    {
        $data = [
            'name' => 'Service without is_active',
            'description' => null,
            'base_price' => 100000.00,
        ];

        $service = $this->repository->create($data);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertDatabaseHas('services', [
            'name' => 'Service without is_active',
            'is_active' => true, // Default should be true
        ]);
    }

    public function test_update(): void
    {
        $service = Service::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'base_price' => 100000.00,
            'is_active' => true,
        ]);

        $updated = $this->repository->update($service, [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'base_price' => 200000.00,
            'is_active' => false,
        ]);

        $this->assertInstanceOf(Service::class, $updated);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'base_price' => 200000.00,
            'is_active' => false,
        ]);
    }

    public function test_update_with_partial_data(): void
    {
        $service = Service::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'base_price' => 100000.00,
            'is_active' => true,
        ]);

        $updated = $this->repository->update($service, [
            'name' => 'Only Name Changed',
        ]);

        $this->assertInstanceOf(Service::class, $updated);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Only Name Changed',
            'description' => 'Original description', // Unchanged
            'base_price' => 100000.00, // Unchanged
            'is_active' => true, // Unchanged
        ]);
    }

    public function test_delete(): void
    {
        $service = Service::factory()->create();

        $this->repository->delete($service);

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_toggle_active(): void
    {
        $service = Service::factory()->create(['is_active' => true]);

        $toggled = $this->repository->toggleActive($service);

        $this->assertInstanceOf(Service::class, $toggled);
        $this->assertFalse($toggled->is_active);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'is_active' => false,
        ]);

        // Toggle back
        $toggledAgain = $this->repository->toggleActive($toggled);

        $this->assertTrue($toggledAgain->is_active);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'is_active' => true,
        ]);
    }
}
