<?php

namespace App\DTOs\Mechanic;

use App\Enums\MechanicAssignmentStatus;
use Illuminate\Http\Request;

readonly class MechanicAssignmentData
{
    public function __construct(
        public string $work_order_service_id,
        public string $mechanic_id,
        public MechanicAssignmentStatus $status,
        public ?string $assigned_at = null,
    ) {}

    /**
     * Build DTO from Laravel Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            work_order_service_id: $request->validated('work_order_service_id'),
            mechanic_id: $request->validated('mechanic_id'),
            // Default status ke PENDING jika baru di-assign
            status: $request->has('status')
                ? MechanicAssignmentStatus::tryFrom($request->validated('status'))
                : MechanicAssignmentStatus::ASSIGNED,
            assigned_at: $request->validated('assigned_at') ?? now()->toDateTimeString(),
        );
    }

    public function toArray(): array
    {
        return [
            'work_order_service_id' => $this->work_order_service_id,
            'mechanic_id'           => $this->mechanic_id,
            'status'                => $this->status->value,
            'assigned_at'           => $this->assigned_at,
        ];
    }
}
