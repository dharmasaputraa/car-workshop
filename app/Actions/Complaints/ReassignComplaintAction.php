<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class ReassignComplaintAction
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaintRepository,
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    /**
     * Reassign a complaint for rework.
     * Changes complaint status to IN_PROGRESS and work order to IN_PROGRESS.
     *
     * @param string $complaintId
     * @return Complaint
     * @throws Exception
     */
    public function execute(string $complaintId): Complaint
    {
        $complaint = $this->complaintRepository->findById($complaintId);

        // Validate complaint status
        if ($complaint->status !== ComplaintStatus::PENDING->value) {
            throw new Exception("Only pending complaints can be reassigned.");
        }

        // Update complaint status to IN_PROGRESS
        $complaint = $this->complaintRepository->updateStatus($complaint, ComplaintStatus::IN_PROGRESS->value);

        // Update work order status to IN_PROGRESS
        $workOrder = $this->workOrderRepository->findById($complaint->work_order_id);
        $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::IN_PROGRESS->value);

        // Load relationships
        $complaint = $this->complaintRepository->loadMissingRelations(
            $complaint,
            ['workOrder', 'workOrder.car', 'workOrder.car.owner', 'complaintServices', 'complaintServices.service']
        );

        return $complaint;
    }
}
