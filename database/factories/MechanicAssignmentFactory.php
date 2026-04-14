<?php

namespace Database\Factories;

use App\Enums\MechanicAssignmentStatus;
use App\Models\MechanicAssignment;
use App\Models\WorkOrderService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MechanicAssignmentFactory extends Factory
{
    protected $model = MechanicAssignment::class;

    public function definition(): array
    {
        $workOrderService = WorkOrderService::factory()->create();
        $mechanic = User::factory()->create();

        return [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $mechanic->id,
            'status' => MechanicAssignmentStatus::ASSIGNED,
            'assigned_at' => now(),
            'completed_at' => null,
        ];
    }

    public function assigned(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => MechanicAssignmentStatus::ASSIGNED,
            'assigned_at' => now(),
            'completed_at' => null,
        ]);
    }

    public function inProgress(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => MechanicAssignmentStatus::IN_PROGRESS,
            'assigned_at' => now()->subMinutes(30),
            'completed_at' => null,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => MechanicAssignmentStatus::COMPLETED,
            'assigned_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }

    public function canceled(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => MechanicAssignmentStatus::CANCELED,
            'assigned_at' => now()->subHours(1),
            'completed_at' => null,
        ]);
    }
}
