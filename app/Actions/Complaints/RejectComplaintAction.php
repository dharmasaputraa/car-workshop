<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class RejectComplaintAction
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaintRepository,
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    /**
     * Reject a complaint.
     * Changes complaint status to REJECTED and work order back to COMPLETED.
     *
     * @param string $complaintId
     * @return Complaint
     * @throws Exception
     */
    public function execute(string $complaintId): Complaint
    {
        $complaint = $this->complaintRepository->findById($complaintId);

        // Validate complaint status
        if ($complaint->status !== ComplaintStatus::PENDING->value && $complaint->status !== ComplaintStatus::IN_PROGRESS->value) {
            throw new Exception("Only pending or in-progress complaints can be rejected.");
        }

        // Update complaint status to REJECTED
        $complaint = $this->complaintRepository->updateStatus($complaint, ComplaintStatus::REJECTED->value);

        // Update work order status back to COMPLETED
        $workOrder = $this->workOrderRepository->findById($complaint->work_order_id);
        $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::COMPLETED->value);

        // Load relationships
        $complaint = $this->complaintRepository->loadMissingRelations(
            $complaint,
            ['workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service']
        );

        return $complaint;
    }
}
