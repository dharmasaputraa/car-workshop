<?php

namespace App\Actions\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderApproved;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class ApproveWorkOrderProposalAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        if ($workOrder->status !== WorkOrderStatus::DIAGNOSED) {
            throw new Exception("Only Work Orders with DIAGNOSED status can be approved.");
        }

        $workOrder = $this->workOrderRepository->updateStatus(
            $workOrder,
            WorkOrderStatus::APPROVED->value
        );

        // Load relationships needed for the email notification
        $workOrder = $this->workOrderRepository->loadMissingRelations(
            $workOrder,
            ['creator', 'car']
        );

        // Dispatch event to notify the creator/admin
        WorkOrderApproved::dispatch($workOrder);

        return $workOrder;
    }
}
