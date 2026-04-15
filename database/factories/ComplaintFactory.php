<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'description' => $this->faker->sentence(),
            'status' => ComplaintStatus::PENDING->value,
            'in_progress_at' => null,
            'resolved_at' => null,
            'rejected_at' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => ComplaintStatus::PENDING->value,
        ]);
    }

    public function inProgress(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => ComplaintStatus::IN_PROGRESS->value,
            'in_progress_at' => now(),
        ]);
    }

    public function resolved(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => ComplaintStatus::RESOLVED->value,
            'resolved_at' => now(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => ComplaintStatus::REJECTED->value,
            'rejected_at' => now(),
        ]);
    }
}
