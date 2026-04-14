<?php

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Models\Car;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        $car = Car::factory()->create();
        $creator = User::factory()->create();

        return [
            'order_number' => 'WO-' . date('Y') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'car_id' => $car->id,
            'created_by' => $creator->id,
            'status' => WorkOrderStatus::PENDING,
            'diagnosis_notes' => $this->faker->optional()->paragraph(),
            'estimated_completion' => $this->faker->optional()->date(),
        ];
    }
}
