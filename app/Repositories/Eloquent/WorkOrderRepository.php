<?php

namespace App\Repositories\Eloquent;

use App\Enums\RoleType;
use App\Enums\ServiceItemStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WorkOrderRepository implements WorkOrderRepositoryInterface
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

        // Super Admin and Admin see all work orders
        if ($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return $query;
        }

        // Customer sees only work orders for their own cars
        if ($user->hasRole(RoleType::CUSTOMER->value)) {
            return $query->whereHas('car', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        }

        // Mechanic sees only work orders where they have active assignments
        if ($user->hasRole(RoleType::MECHANIC->value)) {
            return $query->whereHas('workOrderServices.mechanicAssignments', function ($q) use ($user) {
                $q->where('mechanic_id', $user->id)
                    ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value);
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedWorkOrders(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(WorkOrder::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('car_id'),
                AllowedFilter::exact('created_by'),
                AllowedFilter::partial('order_number'),
            )
            ->allowedSorts('order_number', 'created_at', 'estimated_completion', 'status')
            ->allowedIncludes('car', 'car.owner', 'creator', 'workOrderServices', 'workOrderServices.service')
            ->defaultSort('-created_at');

        return $this->applyDataIsolation($query)
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): WorkOrder
    {
        $query = QueryBuilder::for(WorkOrder::class)
            ->allowedIncludes('car', 'car.owner', 'creator', 'workOrderServices', 'workOrderServices.service');

        return $this->applyDataIsolation($query)->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function create(array $data): WorkOrder
    {
        return WorkOrder::create([
            'order_number' => $data['order_number'],
            'car_id' => $data['car_id'],
            'created_by' => $data['created_by'],
            'status' => $data['status'],
            'diagnosis_notes' => $data['diagnosis_notes'] ?? null,
            'estimated_completion' => $data['estimated_completion'] ?? null,
        ]);
    }

    public function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        $workOrder->update($data);

        return $workOrder;
    }

    public function updateStatus(WorkOrder $workOrder, string $status): WorkOrder
    {
        $workOrder->update(['status' => $status]);

        return $workOrder;
    }

    public function delete(WorkOrder $workOrder): void
    {
        $workOrder->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function loadRelations(WorkOrder $workOrder, array $relations): WorkOrder
    {
        return $workOrder->load($relations);
    }

    public function loadMissingRelations(WorkOrder $workOrder, array $relations): WorkOrder
    {
        return $workOrder->loadMissing($relations);
    }
}
