<?php

namespace App\Actions\WorkOrders;

use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderCompleted;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class CompleteWorkOrderAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
            throw new Exception("Only Work Orders with IN_PROGRESS status can be completed.");
        }

        $unfinishedServicesCount = $workOrder->workOrderServices()
            ->where('status', '!=', ServiceItemStatus::COMPLETED->value)
            ->count();

        if ($unfinishedServicesCount > 0) {
            throw new Exception("Unable to complete the Work Order. There are still {$unfinishedServicesCount} unfinished services.");
        }

        $workOrder = $this->workOrderRepository->updateStatus(
            $workOrder,
            WorkOrderStatus::COMPLETED->value
        );

        // Load car and owner relationships for email notification purposes
        $workOrder = $this->workOrderRepository->loadMissingRelations(
            $workOrder,
            ['car.owner']
        );

        // Trigger the completion event
        WorkOrderCompleted::dispatch($workOrder);

        return $workOrder;
    }
}
