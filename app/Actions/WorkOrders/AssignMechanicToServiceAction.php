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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class AssignMechanicToServiceAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository,
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository,
        protected MechanicAssignmentRepositoryInterface $mechanicAssignmentRepository
    ) {}

    /**
     * Assign multiple mechanics to a work order service.
     *
     * @param string $workOrderServiceId
     * @param array $mechanicIds Array of mechanic UUIDs
     * @return Collection Collection of created MechanicAssignment objects
     * @throws Exception
     */
    public function execute(string $workOrderServiceId, array $mechanicIds): Collection
    {
        return DB::transaction(function () use ($workOrderServiceId, $mechanicIds) {

            // 1. Find service details
            $woService = $this->workOrderServiceRepository->findById($workOrderServiceId);
            $workOrder = $woService->workOrder;

            // 2. Validate Work Order State
            if (!in_array($workOrder->status, [WorkOrderStatus::APPROVED, WorkOrderStatus::IN_PROGRESS])) {
                throw new Exception("Mechanics can only be assigned to Work Orders that are Approved or In Progress.");
            }

            // 3. Create assignments for each mechanic
            $assignments = new Collection();

            foreach ($mechanicIds as $mechanicId) {
                // Check for duplicate assignment (same mechanic to same service)
                $existingAssignment = $woService->mechanicAssignments()
                    ->where('mechanic_id', $mechanicId)
                    ->where('status', '!=', MechanicAssignmentStatus::CANCELED->value)
                    ->first();

                if ($existingAssignment) {
                    // Skip this mechanic if already assigned, or throw exception
                    // For now, we'll skip and continue with other mechanics
                    continue;
                }

                // Create Mechanic Assignment
                $assignment = $this->mechanicAssignmentRepository->create([
                    'work_order_service_id' => $workOrderServiceId,
                    'mechanic_id'           => $mechanicId,
                    'status'                => MechanicAssignmentStatus::ASSIGNED->value,
                    'assigned_at'           => now(),
                ]);

                $assignments->push($assignment);

                // Load relationships for the notification
                $assignment->loadMissing(['mechanic', 'workOrderService.service', 'workOrderService.workOrder.car']);

                // Trigger Event for each assignment
                MechanicAssigned::dispatch($assignment);
            }

            // 4. Update WO Service status to ASSIGNED (not IN_PROGRESS)
            // Only if at least one assignment was created
            if ($assignments->isNotEmpty()) {
                $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::ASSIGNED->value);

                // 5. Update main Work Order to IN_PROGRESS (if it was APPROVED)
                if ($workOrder->status === WorkOrderStatus::APPROVED) {
                    $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::IN_PROGRESS->value);
                }
            }

            return $assignments;
        });
    }
}
