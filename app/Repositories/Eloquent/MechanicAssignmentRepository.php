<?php

namespace App\Repositories\Eloquent;

use App\Enums\RoleType;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MechanicAssignmentRepository implements MechanicAssignmentRepositoryInterface
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

        // Super Admin and Admin see all assignments
        if ($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return $query;
        }

        // Mechanic sees only their own assignments
        if ($user->hasRole(RoleType::MECHANIC->value)) {
            return $query->where('mechanic_id', $user->id);
        }

        // Customers should not have access to assignments
        // Return an empty query if they somehow get here
        if ($user->hasRole(RoleType::CUSTOMER->value)) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function getPaginatedAssignments(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(MechanicAssignment::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('mechanic_id'),
                AllowedFilter::exact('work_order_service_id'),
            )
            ->allowedSorts('assigned_at', 'completed_at', 'created_at')
            ->allowedIncludes('mechanic', 'workOrderService', 'workOrderService.service')
            ->defaultSort('-assigned_at');

        return $this->applyDataIsolation($query)
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): MechanicAssignment
    {
        $query = QueryBuilder::for(MechanicAssignment::class)
            ->allowedIncludes('mechanic', 'workOrderService', 'workOrderService.service');

        return $this->applyDataIsolation($query)->findOrFail($id);
    }

    public function create(array $data): MechanicAssignment
    {
        return MechanicAssignment::create($data);
    }

    public function update(MechanicAssignment $assignment, array $data): MechanicAssignment
    {
        $assignment->update($data);
        return $assignment;
    }

    public function delete(MechanicAssignment $assignment): void
    {
        $assignment->delete();
    }

    /**
     * Update the status of all active (non-canceled) assignments for a specific WorkOrderService.
     */
    public function updateStatusesByWorkOrderService(string $workOrderServiceId, string $status): int
    {
        return MechanicAssignment::where('work_order_service_id', $workOrderServiceId)
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value)
            ->update(['status' => $status]);
    }

    /**
     * Mark all active (non-canceled) assignments for a specific WorkOrderService as COMPLETED.
     * Sets both status and completed_at timestamp.
     */
    public function completeByWorkOrderService(string $workOrderServiceId): int
    {
        return MechanicAssignment::where('work_order_service_id', $workOrderServiceId)
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value)
            ->update([
                'status' => \App\Enums\MechanicAssignmentStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);
    }
}
