<?php

namespace App\Actions\WorkOrders;

use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderDiagnosed;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Exception;

class DiagnoseWorkOrderAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository,
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository
    ) {}

    /**
     * @param string $workOrderId UUID Work Order
     * @param array $servicesData Array of services (service_id, price, notes)
     * @param string|null $diagnosisNotes
     */
    public function execute(string $workOrderId, array $servicesData, ?string $diagnosisNotes): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        // Allow diagnosis on DRAFT (first time) or DIAGNOSED (rediagnosis)
        if (! in_array($workOrder->status, [WorkOrderStatus::DRAFT, WorkOrderStatus::DIAGNOSED])) {
            throw new Exception("Only Work Orders with DRAFT or DIAGNOSED status can be diagnosed.");
        }

        $this->workOrderRepository->update($workOrder, [
            'diagnosis_notes' => $diagnosisNotes
        ]);

        $this->workOrderServiceRepository->cancelAllServices($workOrder);

        if (!empty($servicesData)) {
            $this->workOrderServiceRepository->addServicesToWorkOrder($workOrder, $servicesData);
        }

        $this->workOrderRepository->updateStatus(
            $workOrder,
            WorkOrderStatus::DIAGNOSED->value
        );

        $workOrder = $this->workOrderRepository->loadMissingRelations(
            $workOrder,
            ['car.owner', 'creator', 'workOrderServices' => function ($query) {
                $query->where('status', '!=', ServiceItemStatus::CANCELED->value);
            }, 'workOrderServices.service']
        );

        WorkOrderDiagnosed::dispatch($workOrder);

        return $workOrder;
    }
}
