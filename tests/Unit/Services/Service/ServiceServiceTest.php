<?php

namespace Tests\Unit\Services\Service;

use App\DTOs\Service\StoreServiceData;
use App\DTOs\Service\UpdateServiceData;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Services\Service\ServiceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery\MockInterface;
use Tests\TestCase;

class ServiceServiceTest extends TestCase
{
    /** @var MockInterface|ServiceRepositoryInterface */
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = \Mockery::mock(ServiceRepositoryInterface::class);
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_services_delegates_to_repository(): void
    {
        $paginator = \Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $this->repositoryMock
            ->shouldReceive('getPaginatedServices')
            ->once()
            ->andReturn($paginator);

        $service = new ServiceService($this->repositoryMock);
        $result = $service->getPaginatedServices();

        $this->assertSame($paginator, $result);
    }

    public function test_get_service_by_id_delegates_to_repository(): void
    {
        $service = \Mockery::mock(Service::class);
        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($service);

        $serviceService = new ServiceService($this->repositoryMock);
        $result = $serviceService->getServiceById('test-id');

        $this->assertSame($service, $result);
    }

    public function test_get_service_by_id_throws_when_not_found(): void
    {
        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('invalid-id')
            ->andThrow(new ModelNotFoundException('Service not found'));

        $serviceService = new ServiceService($this->repositoryMock);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Service not found');

        $serviceService->getServiceById('invalid-id');
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_service_delegates_to_repository(): void
    {
        $dto = new StoreServiceData(
            name: 'Oil Change',
            description: 'Full oil change service',
            basePrice: 150000.00,
            isActive: true
        );

        $service = \Mockery::mock(Service::class);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Oil Change',
                'description' => 'Full oil change service',
                'base_price' => 150000.00,
                'is_active' => true,
            ])
            ->andReturn($service);

        $serviceService = new ServiceService($this->repositoryMock);
        $result = $serviceService->createService($dto);

        $this->assertSame($service, $result);
    }

    public function test_update_service_delegates_to_repository(): void
    {
        $service = \Mockery::mock(Service::class);

        $dto = new UpdateServiceData(
            name: 'Updated Service Name',
            description: 'Updated description',
            basePrice: 200000.00,
            isActive: false
        );

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($service, [
                'name' => 'Updated Service Name',
                'description' => 'Updated description',
                'base_price' => 200000.00,
                'is_active' => false,
            ])
            ->andReturn($service);

        $serviceService = new ServiceService($this->repositoryMock);
        $result = $serviceService->updateService($service, $dto);

        $this->assertSame($service, $result);
    }

    public function test_delete_service_delegates_to_repository(): void
    {
        $service = \Mockery::mock(Service::class);

        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($service);

        $serviceService = new ServiceService($this->repositoryMock);
        $serviceService->deleteService($service);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_toggle_active_delegates_to_repository(): void
    {
        $service = \Mockery::mock(Service::class);

        $this->repositoryMock
            ->shouldReceive('toggleActive')
            ->once()
            ->with($service)
            ->andReturn($service);

        $serviceService = new ServiceService($this->repositoryMock);
        $result = $serviceService->toggleActive($service);

        $this->assertSame($service, $result);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
