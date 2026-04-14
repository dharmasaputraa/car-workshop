<?php

namespace App\Actions\WorkOrders;

use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class CompleteWorkOrderServiceAction
{
    public function __construct(
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository,
        protected MechanicAssignmentRepositoryInterface $mechanicAssignmentRepository
    ) {}

    /**
     * Complete a WorkOrderService and set all active mechanic assignments to COMPLETED.
     *
     * @param string $workOrderServiceId
     * @return WorkOrderService
     * @throws Exception
     */
    public function execute(string $workOrderServiceId): WorkOrderService
    {
        return DB::transaction(function () use ($workOrderServiceId) {
            // 1. Find the WorkOrderService with its WorkOrder
            $woService = $this->workOrderServiceRepository->findById($workOrderServiceId);
            $workOrder = $woService->workOrder;

            // 2. Validate WorkOrderService status - must be IN_PROGRESS
            if ($woService->status !== ServiceItemStatus::IN_PROGRESS) {
                throw new Exception("Work Order Service must be in IN_PROGRESS status to complete. Current status: {$woService->status->value}");
            }

            // 3. Validate WorkOrder status - must be IN_PROGRESS
            if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
                throw new Exception("Work Order must be IN_PROGRESS to complete a service. Current status: {$workOrder->status->value}");
            }

            // 4. Update WorkOrderService status to COMPLETED
            $woService = $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::COMPLETED->value);

            // 5. Complete all active mechanic assignments (sets status + completed_at)
            $this->mechanicAssignmentRepository->completeByWorkOrderService($workOrderServiceId);

            // 6. Reload relations for the response
            $woService->loadMissing(['workOrder', 'service', 'mechanicAssignments' => function ($query) {
                $query->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value)
                    ->with('mechanic');
            }]);

            return $woService;
        });
    }
}
