<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Events\ComplaintResolved;
use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintServiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class ResolveComplaintAction
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaintRepository,
        protected ComplaintServiceRepositoryInterface $complaintServiceRepository,
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    /**
     * Resolve a complaint.
     * Only allowed when all complaint services are completed.
     * Changes complaint status to RESOLVED and work order back to COMPLETED.
     *
     * @param string $complaintId
     * @return Complaint
     * @throws Exception
     */
    public function execute(string $complaintId): Complaint
    {
        $complaint = $this->complaintRepository->findById($complaintId);

        // Validate complaint status
        if ($complaint->status !== ComplaintStatus::IN_PROGRESS->value) {
            throw new Exception("Only in-progress complaints can be resolved.");
        }

        // Check if all complaint services are completed
        if (! $this->complaintServiceRepository->areAllServicesCompleted($complaint)) {
            throw new Exception("All complaint services must be completed before resolving the complaint.");
        }

        // Update complaint status to RESOLVED
        $complaint = $this->complaintRepository->updateStatus($complaint, ComplaintStatus::RESOLVED->value);

        // Update work order status back to COMPLETED
        $workOrder = $this->workOrderRepository->findById($complaint->work_order_id);
        $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::COMPLETED->value);

        // Load relationships
        $complaint = $this->complaintRepository->loadMissingRelations(
            $complaint,
            ['workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service']
        );

        // Dispatch event
        ComplaintResolved::dispatch($complaint);

        return $complaint;
    }
}
