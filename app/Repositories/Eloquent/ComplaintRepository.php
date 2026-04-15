<?php

namespace App\Repositories\Eloquent;

use App\Enums\RoleType;
use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ComplaintRepository implements ComplaintRepositoryInterface
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

        // Super Admin and Admin see all complaints
        if ($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return $query;
        }

        // Customer sees only complaints on their own work orders
        if ($user->hasRole(RoleType::CUSTOMER->value)) {
            return $query->whereHas('workOrder.car', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        }

        // Mechanic sees only complaints where they are assigned to complaint services
        if ($user->hasRole(RoleType::MECHANIC->value)) {
            return $query->whereHas('complaintServices.mechanicAssignments', function ($q) use ($user) {
                $q->where('mechanic_id', $user->id);
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getAll(): Collection
    {
        $query = QueryBuilder::for(Complaint::class)
            ->allowedIncludes('workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service', 'complaintServices.mechanicAssignments');

        return $this->applyDataIsolation($query)->get();
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Complaint::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('work_order_id'),
            )
            ->allowedSorts('created_at', 'status')
            ->allowedIncludes('workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service', 'complaintServices.mechanicAssignments')
            ->defaultSort('-created_at');

        return $this->applyDataIsolation($query)
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function findById(string $id): Complaint
    {
        $query = QueryBuilder::for(Complaint::class)
            ->allowedIncludes('workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service', 'complaintServices.mechanicAssignments');

        return $this->applyDataIsolation($query)->findOrFail($id);
    }

    public function findByWorkOrderId(string $workOrderId): ?Complaint
    {
        $query = Complaint::where('work_order_id', $workOrderId);

        return $this->applyDataIsolation($query)->first();
    }

    public function getByMechanicId(string $mechanicId): Collection
    {
        return Complaint::whereHas('complaintServices.mechanicAssignments', function ($q) use ($mechanicId) {
            $q->where('mechanic_id', $mechanicId);
        })->get();
    }

    public function getByCustomerId(string $customerId): Collection
    {
        return Complaint::whereHas('workOrder.car', function ($q) use ($customerId) {
            $q->where('owner_id', $customerId);
        })->get();
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Complaint
    {
        return Complaint::create([
            'work_order_id' => $data['work_order_id'],
            'description' => $data['description'],
            'status' => $data['status'] ?? \App\Enums\ComplaintStatus::PENDING->value,
        ]);
    }

    public function update(Complaint $complaint, array $data): Complaint
    {
        $complaint->update($data);

        return $complaint;
    }

    public function delete(Complaint $complaint): bool
    {
        return $complaint->delete();
    }

    public function updateStatus(Complaint $complaint, string $status): Complaint
    {
        $data = ['status' => $status];

        // Update timestamp based on status
        match ($status) {
            \App\Enums\ComplaintStatus::IN_PROGRESS->value => $data['in_progress_at'] = now(),
            \App\Enums\ComplaintStatus::RESOLVED->value => $data['resolved_at'] = now(),
            \App\Enums\ComplaintStatus::REJECTED->value => $data['rejected_at'] = now(),
            default => null,
        };

        $complaint->update($data);

        return $complaint;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function loadMissingRelations(Complaint $complaint, array $relations): Complaint
    {
        return $complaint->loadMissing($relations);
    }
}
