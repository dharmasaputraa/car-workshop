<?php

namespace App\Actions\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class CancelWorkOrderAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $id): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($id);

        // Daftar status yang TIDAK BOLEH di-cancel
        $uncancelableStates = [
            WorkOrderStatus::IN_PROGRESS,
            WorkOrderStatus::COMPLETED,
            WorkOrderStatus::COMPLAINED,
            WorkOrderStatus::CLOSED,
            WorkOrderStatus::CANCELED, // Mencegah cancel 2 kali
        ];

        if (in_array($workOrder->status, $uncancelableStates)) {
            throw new Exception("Cannot cancel Work Orders that are currently in progress, completed, closed, or already canceled.");
        }

        // Ubah status menjadi CANCELED alih-alih mendelete data
        $workOrder = $this->workOrderRepository->updateStatus(
            $workOrder,
            WorkOrderStatus::CANCELED->value
        );

        // (Opsional) Jika Anda ingin membuat notifikasi email saat dicancel:
        // WorkOrderCanceled::dispatch($workOrder);

        return $workOrder;
    }
}
