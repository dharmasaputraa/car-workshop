<?php

namespace App\Repositories\Contracts;

use App\Models\MechanicAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MechanicAssignmentRepositoryInterface
{
    public function getPaginatedAssignments(): LengthAwarePaginator;
    public function findById(string $id): MechanicAssignment;
    public function create(array $data): MechanicAssignment;
    public function update(MechanicAssignment $assignment, array $data): MechanicAssignment;
    public function delete(MechanicAssignment $assignment): void;

    /**
     * Update the status of all active (non-canceled) assignments for a specific WorkOrderService.
     *
     * @param string $workOrderServiceId
     * @param string $status
     * @return int Number of updated assignments
     */
    public function updateStatusesByWorkOrderService(string $workOrderServiceId, string $status): int;

    /**
     * Mark all active (non-canceled) assignments for a specific WorkOrderService as COMPLETED.
     * Sets both status and completed_at timestamp.
     *
     * @param string $workOrderServiceId
     * @return int Number of completed assignments
     */
    public function completeByWorkOrderService(string $workOrderServiceId): int;
}
