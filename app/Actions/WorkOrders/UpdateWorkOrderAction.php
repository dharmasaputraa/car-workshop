<?php

namespace App\Actions\WorkOrders;

use App\DTOs\WorkOrder\UpdateWorkOrderData;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class UpdateWorkOrderAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $id, UpdateWorkOrderData $data): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($id);

        if ($workOrder->status !== WorkOrderStatus::DRAFT) {
            throw new Exception("Only Work Orders with DRAFT status can be changed directly.");
        }

        return $this->workOrderRepository->update($workOrder, $data->toArray());
    }
}
