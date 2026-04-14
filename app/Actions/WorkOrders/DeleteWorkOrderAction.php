<?php

namespace App\Actions\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class DeleteWorkOrderAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $id): void
    {
        $workOrder = $this->workOrderRepository->findById($id);

        if (in_array($workOrder->status, [WorkOrderStatus::IN_PROGRESS, WorkOrderStatus::COMPLETED, WorkOrderStatus::CLOSED])) {
            throw new Exception("Cannot delete Work Orders that are currently being worked on or have been completed.");
        }

        $this->workOrderRepository->delete($workOrder);
    }
}
