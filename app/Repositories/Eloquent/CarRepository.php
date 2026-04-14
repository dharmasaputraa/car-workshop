<?php

namespace App\Repositories\Eloquent;

use App\Enums\RoleType;
use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CarRepository implements CarRepositoryInterface
{
    private const PER_PAGE = 15;

    /*
    |--------------------------------------------------------------------------
    | DATA ISOLATION
    |--------------------------------------------------------------------------
    */

    private function applyDataIsolation($query)
    {
        $user = Auth::user();

        if (! $user) {
            return $query;
        }

        // Super Admin and Admin see all cars
        if ($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return $query;
        }

        // Customer sees only their own cars
        if ($user->hasRole(RoleType::CUSTOMER->value)) {
            return $query->where('owner_id', $user->id);
        }

        // Mechanic sees only cars linked to their assigned work orders
        if ($user->hasRole(RoleType::MECHANIC->value)) {
            return $query->whereHas('workOrders.workOrderServices.mechanicAssignments', function ($q) use ($user) {
                $q->where('mechanic_id', $user->id)
                    ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value);
            });
        }

        return $query;
    }

    public function getPaginatedCars(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Car::class)
            ->allowedFilters(
                AllowedFilter::partial('plate_number'),
                AllowedFilter::partial('brand'),
                AllowedFilter::exact('owner_id'),
            )
            ->allowedSorts('brand', 'year', 'created_at')
            ->allowedIncludes('owner', 'workOrders')
            ->defaultSort('-created_at');

        return $this->applyDataIsolation($query)
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): Car
    {
        $query = QueryBuilder::for(Car::class)
            ->allowedIncludes('owner', 'workOrders');

        return $this->applyDataIsolation($query)->findOrFail($id);
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
