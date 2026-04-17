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
        if ($complaint->status !== ComplaintStatus::PENDING && $complaint->status !== ComplaintStatus::IN_PROGRESS) {
            throw new Exception("Only pending or in-progress complaints can be rejected.");
        }

        // Update complaint status to REJECTED
        $complaint = $this->complaintRepository->updateStatus($complaint, ComplaintStatus::REJECTED->value);

        // Check if there are other active complaints on this work order
        $activeComplaint = $this->complaintRepository->findActiveByWorkOrderId($complaint->work_order_id);

        // Update work order status
        $workOrder = $this->workOrderRepository->findById($complaint->work_order_id);
        if ($activeComplaint) {
            // Still has active complaints, keep as COMPLAINED
            $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::COMPLAINED->value);
        } else {
            // No more active complaints, back to COMPLETED
            $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::COMPLETED->value);
        }

        // Load relationships
        $complaint = $this->complaintRepository->loadMissingRelations(
            $complaint,
            ['workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service']
        );

        return $complaint;
    }
}
