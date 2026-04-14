<?php

namespace App\Repositories\Eloquent;

use App\Enums\ServiceItemStatus;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WorkOrderServiceRepository implements WorkOrderServiceRepositoryInterface
{
    public function __construct(
        protected ServiceRepositoryInterface $serviceRepository
    ) {}

    /**
     * Add services to a work order.
     * For each service_id, fetches the Service model to get base_price,
     * sets status to PENDING, and creates WorkOrderService records.
     */
    public function addServicesToWorkOrder(WorkOrder $workOrder, array $servicesData): void
    {
        foreach ($servicesData as $serviceData) {
            $serviceId = $serviceData['service_id'];
            $notes = $serviceData['notes'] ?? null;

            // Fetch the Service to get base_price
            $service = $this->serviceRepository->findById($serviceId);

            // Create WorkOrderService with auto-fetched price and PENDING status
            $workOrder->workOrderServices()->create([
                'service_id' => $serviceId,
                'price' => $service->base_price,
                'status' => ServiceItemStatus::PENDING->value,
                'notes' => $notes,
            ]);
        }
    }

    /**
     * Cancel all services for a work order.
     * Changes status of all non-canceled services to CANCELED.
     */
    public function cancelAllServices(WorkOrder $workOrder): void
    {
        $workOrder->workOrderServices()
            ->where('status', '!=', ServiceItemStatus::CANCELED->value)
            ->update([
                'status' => ServiceItemStatus::CANCELED->value,
            ]);
    }

    /**
     * Find a WorkOrderService by ID.
     */
    public function findById(string $id): WorkOrderService
    {
        return WorkOrderService::with('workOrder')->findOrFail($id);
    }

    /**
     * Update the status of a WorkOrderService.
     */
    public function updateStatus(WorkOrderService $woService, string $status): WorkOrderService
    {
        $woService->update(['status' => $status]);
        return $woService;
    }

    /**
     * Check if a WorkOrderService has any active (non-canceled) mechanic assignments.
     */
    public function hasActiveAssignments(WorkOrderService $woService): bool
    {
        return $woService->mechanicAssignments()
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value)
            ->exists();
    }

    /**
     * Check if a WorkOrderService has any uncompleted (non-completed, non-canceled) mechanic assignments.
     */
    public function hasUncompletedAssignments(WorkOrderService $woService): bool
    {
        return $woService->mechanicAssignments()
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value)
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::COMPLETED->value)
            ->exists();
    }
}
