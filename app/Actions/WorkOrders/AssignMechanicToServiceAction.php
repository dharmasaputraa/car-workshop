<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\MechanicAssigned;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class AssignMechanicToServiceAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository,
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository,
        protected MechanicAssignmentRepositoryInterface $mechanicAssignmentRepository
    ) {}

    public function execute(string $workOrderServiceId, string $mechanicId): MechanicAssignment
    {
        return DB::transaction(function () use ($workOrderServiceId, $mechanicId) {

            // 1. Find service details
            $woService = $this->workOrderServiceRepository->findById($workOrderServiceId);
            $workOrder = $woService->workOrder;

            // 2. Validate Work Order State
            if (!in_array($workOrder->status, [WorkOrderStatus::APPROVED, WorkOrderStatus::IN_PROGRESS])) {
                throw new Exception("Mechanics can only be assigned to Work Orders that are Approved or In Progress.");
            }

            // 3. Check for duplicate assignment (same mechanic to same service)
            $existingAssignment = $woService->mechanicAssignments()
                ->where('mechanic_id', $mechanicId)
                ->where('status', '!=', MechanicAssignmentStatus::CANCELED->value)
                ->first();

            if ($existingAssignment) {
                throw new Exception("This mechanic is already assigned to this service.");
            }

            // 4. Create Mechanic Assignment
            $assignment = $this->mechanicAssignmentRepository->create([
                'work_order_service_id' => $workOrderServiceId,
                'mechanic_id'           => $mechanicId,
                'status'                => MechanicAssignmentStatus::ASSIGNED->value,
                'assigned_at'           => now(),
            ]);

            // 5. Update WO Service status to ASSIGNED (not IN_PROGRESS)
            $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::ASSIGNED->value);

            // 6. Update main Work Order to IN_PROGRESS (if it was APPROVED)
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
