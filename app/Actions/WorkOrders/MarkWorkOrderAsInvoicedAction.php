<?php

namespace App\Actions\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Events\SendWorkOrderInvoice;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class MarkWorkOrderAsInvoicedAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        if ($workOrder->status !== WorkOrderStatus::COMPLETED) {
            throw new Exception("Only Work Orders with COMPLETED status can be issued Invoices.");
        }

        $workOrder = $this->workOrderRepository->updateStatus(
            $workOrder,
            WorkOrderStatus::INVOICED->value
        );

        // Trigger invoice creation/sending
        SendWorkOrderInvoice::dispatch($workOrder);

        return $workOrder;
    }
}
