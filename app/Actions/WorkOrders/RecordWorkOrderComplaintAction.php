<?php

namespace App\Actions\WorkOrders;

use App\Enums\ComplaintStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderComplained;
use App\Models\ComplaintService;
use App\Models\WorkOrder;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintServiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class RecordWorkOrderComplaintAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository,
        protected ComplaintRepositoryInterface $complaintRepository,
        protected ComplaintServiceRepositoryInterface $complaintServiceRepository
    ) {}

    /**
     * Record a complaint on a completed work order.
     *
     * @param string $workOrderId
     * @param string $description
     * @param array $servicesData Array of services (service_id, description)
     * @return WorkOrder
     * @throws Exception
     */
    public function execute(string $workOrderId, string $description, array $servicesData): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        // Validate work order status
        if ($workOrder->status !== WorkOrderStatus::COMPLETED) {
            throw new Exception("Only completed work orders can have complaints recorded.");
        }

        // Check if complaint already exists
        $existingComplaint = $this->complaintRepository->findByWorkOrderId($workOrderId);
        if ($existingComplaint) {
            throw new Exception("A complaint already exists for this work order.");
        }

        // Create complaint
        $complaint = $this->complaintRepository->create([
            'work_order_id' => $workOrderId,
            'description' => $description,
            'status' => ComplaintStatus::PENDING->value,
        ]);

        // Create complaint services
        $this->complaintServiceRepository->addServicesToComplaint($complaint, $servicesData);

        // Update work order status to COMPLAINED
        $this->workOrderRepository->updateStatus($workOrder, WorkOrderStatus::COMPLAINED->value);

        // Load relationships
        $workOrder = $this->workOrderRepository->loadMissingRelations(
            $workOrder,
            ['car.owner', 'creator', 'complaint', 'complaint.complaintServices', 'complaint.complaintServices.service']
        );

        // Dispatch event
        WorkOrderComplained::dispatch($workOrder, $complaint);

        return $workOrder;
    }
}
