<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StartMechanicAssignmentAction
{
    public function __construct(
        protected MechanicAssignmentRepositoryInterface $mechanicAssignmentRepository,
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository
    ) {}

    /**
     * Start a specific mechanic's assignment.
     * If this is the first mechanic to start, auto-transition the WorkOrderService to IN_PROGRESS.
     *
     * @param string $assignmentId
     * @return MechanicAssignment
     * @throws Exception
     */
    public function execute(string $assignmentId): MechanicAssignment
    {
        return DB::transaction(function () use ($assignmentId) {
            // 1. Find the assignment with its WorkOrderService
            $assignment = $this->mechanicAssignmentRepository->findById($assignmentId);
            $woService = $assignment->workOrderService;

            // 2. Validate assignment status - must be ASSIGNED
            if ($assignment->status !== MechanicAssignmentStatus::ASSIGNED) {
                throw new Exception("Assignment must be in ASSIGNED status to start. Current status: {$assignment->status->value}");
            }

            // 3. Update assignment status to IN_PROGRESS
            $assignment = $this->mechanicAssignmentRepository->update($assignment, [
                'status' => MechanicAssignmentStatus::IN_PROGRESS->value,
            ]);

            // 4. Check if this is the first mechanic starting the service
            // If WorkOrderService is still ASSIGNED, auto-transition it to IN_PROGRESS
            if ($woService->status === ServiceItemStatus::ASSIGNED) {
                $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::IN_PROGRESS->value);
            }

            // 5. Reload relations for the response
            $assignment->loadMissing(['workOrderService', 'workOrderService.service', 'mechanic']);

            return $assignment;
        });
    }
}
