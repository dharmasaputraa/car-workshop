<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class CompleteMechanicAssignmentAction
{
    public function __construct(
        protected MechanicAssignmentRepositoryInterface $mechanicAssignmentRepository,
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository
    ) {}

    /**
     * Complete a specific mechanic's assignment.
     * If all active mechanics have completed, auto-transition the WorkOrderService to COMPLETED.
     *
     * @param string $assignmentId
     * @return array{assignment: MechanicAssignment, serviceAutoCompleted: bool}
     * @throws Exception
     */
    public function execute(string $assignmentId): array
    {
        return DB::transaction(function () use ($assignmentId) {
            // 1. Find the assignment with its WorkOrderService
            $assignment = $this->mechanicAssignmentRepository->findById($assignmentId);
            $woService = $assignment->workOrderService;

            // 2. Validate assignment status - must be IN_PROGRESS
            if ($assignment->status !== MechanicAssignmentStatus::IN_PROGRESS) {
                throw new Exception("Assignment must be in IN_PROGRESS status to complete. Current status: {$assignment->status->value}");
            }

            // 3. Update assignment status to COMPLETED with timestamp
            $assignment = $this->mechanicAssignmentRepository->update($assignment, [
                'status' => MechanicAssignmentStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);

            // 4. Check if all active mechanics have completed their assignments
            $hasUncompleted = $this->workOrderServiceRepository->hasUncompletedAssignments($woService);

            $serviceAutoCompleted = false;

            // If no uncompleted assignments remain, auto-transition the service to COMPLETED
            if (!$hasUncompleted && $woService->status === ServiceItemStatus::IN_PROGRESS) {
                $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::COMPLETED->value);
                $serviceAutoCompleted = true;
            }

            // 5. Reload relations for the response
            $woService->refresh();
            $assignment->loadMissing(['workOrderService', 'workOrderService.service', 'mechanic']);

            return [
                'assignment' => $assignment,
                'serviceAutoCompleted' => $serviceAutoCompleted,
                'workOrderService' => $woService,
            ];
        });
    }
}
