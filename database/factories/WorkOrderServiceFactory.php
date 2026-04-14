<?php

namespace Database\Factories;

use App\Enums\ServiceItemStatus;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderServiceFactory extends Factory
{
    protected $model = WorkOrderService::class;

    public function definition(): array
    {
        $workOrder = WorkOrder::factory()->create();
        $service = Service::factory()->create();

        return [
            'work_order_id' => $workOrder->id,
            'service_id' => $service->id,
            'price' => $this->faker->randomFloat(2, 50, 500),
            'status' => ServiceItemStatus::PENDING,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
