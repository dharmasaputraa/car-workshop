<?php

namespace App\Actions\WorkOrders;

use App\DTOs\WorkOrder\CreateWorkOrderData;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Support\Str;

class CreateWorkOrderAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    /**
     * Executes the creation of a new Work Order.
     *
     * @param CreateWorkOrderData $data Data transfer object from the request
     * @param string $creatorId UUID of the user (admin/mechanic) who created the WO
     * @return WorkOrder
     */
    public function execute(CreateWorkOrderData $data, string $creatorId): WorkOrder
    {
        $payload = $data->toArray();

        $payload['order_number'] = $this->generateOrderNumber();
        $payload['created_by']   = $creatorId;
        $payload['status']       = WorkOrderStatus::DRAFT->value;

        $workOrder = $this->workOrderRepository->create($payload);

        return $this->workOrderRepository->loadMissingRelations($workOrder, ['car', 'creator']);
    }
    /**
     * Generate WO number format: WO-YYYYMMDD-XXXXX
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $randomStr = strtoupper(Str::random(5));

        return "WO-{$date}-{$randomStr}";
    }
}
