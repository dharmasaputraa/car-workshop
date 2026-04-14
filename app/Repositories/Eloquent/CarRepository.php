<?php

namespace App\Repositories\Eloquent;

use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CarRepository implements CarRepositoryInterface
{
    private const PER_PAGE = 15;

    public function getPaginatedCars(): LengthAwarePaginator
    {
        return QueryBuilder::for(Car::class)
            ->allowedFilters(
                AllowedFilter::partial('plate_number'),
                AllowedFilter::partial('brand'),
                AllowedFilter::exact('owner_id'),
            )
            ->allowedSorts('brand', 'year', 'created_at')
            ->allowedIncludes('owner', 'workOrders')
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): Car
    {
        return QueryBuilder::for(Car::class)
            ->allowedIncludes('owner', 'workOrders')
            ->findOrFail($id);
    }

    public function create(array $data): Car
    {
        return Car::create([
            'owner_id'     => $data['owner_id'],
            'plate_number' => $data['plate_number'],
            'brand'        => $data['brand'],
            'model'        => $data['model'],
            'year'         => $data['year'],
            'color'        => $data['color'],
        ]);
    }

    public function update(Car $car, array $data): Car
    {
        $car->update($data);
        return $car;
    }

    public function delete(Car $car): void
    {
        $car->delete();
    }
}
