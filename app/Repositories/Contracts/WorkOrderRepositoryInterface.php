<?php

namespace App\Repositories\Contracts;

use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WorkOrderRepositoryInterface
{
    // READ
    public function getPaginatedWorkOrders(): LengthAwarePaginator;
    public function findById(string $id): WorkOrder;

    // WRITE
    public function create(array $data): WorkOrder;
    public function update(WorkOrder $workOrder, array $data): WorkOrder;
    public function updateStatus(WorkOrder $workOrder, string $status): WorkOrder;
    public function delete(WorkOrder $workOrder): void;

    // RELATIONS
    public function loadRelations(WorkOrder $workOrder, array $relations): WorkOrder;
    public function loadMissingRelations(WorkOrder $workOrder, array $relations): WorkOrder;
}
