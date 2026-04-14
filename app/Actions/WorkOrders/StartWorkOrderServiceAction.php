<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StartWorkOrderServiceAction
{
    public function __construct(
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository,
        protected MechanicAssignmentRepositoryInterface $mechanicAssignmentRepository
    ) {}

    /**
     * Start a WorkOrderService and set all active mechanic assignments to IN_PROGRESS.
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

            // 2. Validate WorkOrderService status - must be ASSIGNED
            if ($woService->status !== ServiceItemStatus::ASSIGNED) {
                throw new Exception("Work Order Service must be in ASSIGNED status to start. Current status: {$woService->status->value}");
            }

            // 3. Validate WorkOrder status - must be APPROVED or IN_PROGRESS
            if (!in_array($workOrder->status, [WorkOrderStatus::APPROVED, WorkOrderStatus::IN_PROGRESS])) {
                throw new Exception("Work Order must be APPROVED or IN_PROGRESS to start a service. Current status: {$workOrder->status->value}");
            }

            // 4. Update WorkOrderService status to IN_PROGRESS
            $woService = $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::IN_PROGRESS->value);

            // 5. Update all active mechanic assignments to IN_PROGRESS
            $this->mechanicAssignmentRepository->updateStatusesByWorkOrderService(
                $workOrderServiceId,
                MechanicAssignmentStatus::IN_PROGRESS->value
            );

            // 6. Reload relations for the response
            $woService->loadMissing(['workOrder', 'service', 'mechanicAssignments' => function ($query) {
                $query->where('status', '!=', MechanicAssignmentStatus::CANCELED->value)
                    ->with('mechanic');
            }]);

            return $woService;
        });
    }
}
