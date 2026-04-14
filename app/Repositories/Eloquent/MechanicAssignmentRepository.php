<?php

namespace App\Repositories\Eloquent;

use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MechanicAssignmentRepository implements MechanicAssignmentRepositoryInterface
{
    private const PER_PAGE = 15;

    public function getPaginatedAssignments(): LengthAwarePaginator
    {
        return QueryBuilder::for(MechanicAssignment::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('mechanic_id'),
                AllowedFilter::exact('work_order_service_id'),
            )
            ->allowedSorts('assigned_at', 'completed_at', 'created_at')
            ->allowedIncludes('mechanic', 'workOrderService', 'workOrderService.service')
            ->defaultSort('-assigned_at')
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function findById(string $id): MechanicAssignment
    {
        return QueryBuilder::for(MechanicAssignment::class)
            ->allowedIncludes('mechanic', 'workOrderService', 'workOrderService.service')
            ->findOrFail($id);
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
}
