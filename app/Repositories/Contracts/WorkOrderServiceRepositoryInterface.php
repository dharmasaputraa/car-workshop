<?php

namespace App\Repositories\Contracts;

use App\Models\WorkOrder;
use App\Models\WorkOrderService;

interface WorkOrderServiceRepositoryInterface
{
    /**
     * Add services to a work order.
     * For each service_id, fetches the Service model to get base_price,
     * sets status to PENDING, and creates WorkOrderService records.
     *
     * @param WorkOrder $workOrder
     * @param array $servicesData Array of services with structure:
     *                           [
     *                             ['service_id' => 'uuid', 'notes' => 'optional string'],
     *                             ...
     *                           ]
     * @return void
     */
    public function addServicesToWorkOrder(WorkOrder $workOrder, array $servicesData): void;

    /**
     * Cancel all services for a work order.
     * Changes status of all non-canceled services to CANCELED.
     *
     * @param WorkOrder $workOrder
     * @return void
     */
    public function cancelAllServices(WorkOrder $workOrder): void;

    /**
     * Find a WorkOrderService by ID.
     *
     * @param string $id
     * @return WorkOrderService
     */
    public function findById(string $id): WorkOrderService;

    /**
     * Update the status of a WorkOrderService.
     *
     * @param WorkOrderService $woService
     * @param string $status
     * @return WorkOrderService
     */
    public function updateStatus(WorkOrderService $woService, string $status): WorkOrderService;

    /**
     * Check if a WorkOrderService has any active (non-canceled) mechanic assignments.
     *
     * @param WorkOrderService $woService
     * @return bool
     */
    public function hasActiveAssignments(WorkOrderService $woService): bool;

    /**
     * Check if a WorkOrderService has any uncompleted (non-completed, non-canceled) mechanic assignments.
     * Used to determine if all mechanics have finished their work.
     *
     * @param WorkOrderService $woService
     * @return bool
     */
    public function hasUncompletedAssignments(WorkOrderService $woService): bool;
}
