<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceRepository implements ServiceRepositoryInterface
{
    private const PER_PAGE = 15;

    public function getPaginatedServices(): LengthAwarePaginator
    {
        return QueryBuilder::for(Service::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('is_active'),
            )
            ->allowedSorts('name', 'base_price', 'created_at')
            ->allowedIncludes('workOrderServices', 'complaintServices')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): Service
    {
        return QueryBuilder::for(Service::class)
            ->allowedIncludes('workOrderServices', 'complaintServices')
            ->findOrFail($id);
    }

    public function create(array $data): Service
    {
        return Service::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'base_price'  => $data['base_price'],
            'is_active'   => $data['is_active'] ?? true,
        ]);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);
        return $service;
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }

    public function toggleActive(Service $service): Service
    {
        $service->update(['is_active' => !$service->is_active]);
        return $service;
    }
}
