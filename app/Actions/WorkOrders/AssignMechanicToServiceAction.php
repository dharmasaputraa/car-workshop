<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\WorkOrderStatus;
use App\Events\MechanicAssigned;
use App\Models\WorkOrderService;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class AssignMechanicToServiceAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    public function execute(string $workOrderServiceId, string $mechanicId): MechanicAssignment
    {
        return DB::transaction(function () use ($workOrderServiceId, $mechanicId) {

            // 1. Find service details
            $woService = WorkOrderService::with('workOrder')->findOrFail($workOrderServiceId);
            $workOrder = $woService->workOrder;

            // 2. Validate Work Order State
            if (!in_array($workOrder->status, [WorkOrderStatus::APPROVED, WorkOrderStatus::IN_PROGRESS])) {
                throw new Exception("Mechanics can only be assigned to Work Orders that are Approved or In Progress.");
            }

            // 3. Create Mechanic Assignment
            $assignment = MechanicAssignment::create([
                'work_order_service_id' => $workOrderServiceId,
                'mechanic_id'           => $mechanicId,
                'status'                => MechanicAssignmentStatus::ASSIGNED->value,
                'assigned_at'           => now(),
            ]);

            // 4. Update WO Service status to in_progress
            $woService->update(['status' => 'in_progress']); // Adjust with your actual Enum if it exists for service item

            // 5. Update main Work Order to IN_PROGRESS (if it was APPROVED)
            if ($workOrder->status === WorkOrderStatus::APPROVED) {
                $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::IN_PROGRESS->value);
            }

            // Load relationships for the notification
            $assignment->loadMissing(['mechanic', 'workOrderService.service', 'workOrderService.workOrder.car']);

            // Trigger Event
            MechanicAssigned::dispatch($assignment);

            return $assignment;
        });
    }
}
