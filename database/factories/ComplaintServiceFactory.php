<?php

namespace Database\Factories;

use App\Enums\ServiceItemStatus;
use App\Models\Complaint;
use App\Models\ComplaintService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintServiceFactory extends Factory
{
    protected $model = ComplaintService::class;

    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'service_id' => Service::factory(),
            'price' => $this->faker->randomFloat(2, 50, 500),
            'status' => ServiceItemStatus::PENDING->value,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => ServiceItemStatus::PENDING->value,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => ServiceItemStatus::COMPLETED->value,
        ]);
    }
}
