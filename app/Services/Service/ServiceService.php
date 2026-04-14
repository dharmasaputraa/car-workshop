<?php

namespace App\Services\Service;

use App\DTOs\Service\StoreServiceData;
use App\DTOs\Service\UpdateServiceData;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ServiceService
{
    public function __construct(
        protected ServiceRepositoryInterface $serviceRepository
    ) {}

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedServices(): LengthAwarePaginator
    {
        return $this->serviceRepository->getPaginatedServices();
    }

    public function getServiceById(string $id): Service
    {
        return $this->serviceRepository->findById($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function createService(StoreServiceData $data): Service
    {
        return DB::transaction(function () use ($data) {
            return $this->serviceRepository->create($data->toArray());
        });
    }

    public function updateService(Service $service, UpdateServiceData $data): Service
    {
        return DB::transaction(function () use ($service, $data) {
            return $this->serviceRepository->update($service, $data->toArray());
        });
    }

    public function deleteService(Service $service): void
    {
        $this->serviceRepository->delete($service);
    }

    public function toggleActive(Service $service): Service
    {
        return $this->serviceRepository->toggleActive($service);
    }
}
