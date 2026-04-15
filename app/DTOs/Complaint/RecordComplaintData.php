<?php

namespace App\DTOs\Complaint;

use Illuminate\Http\Request;

class RecordComplaintData
{
    public function __construct(
        public readonly string $workOrderId,
        public readonly string $description,
        public readonly array $services
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            workOrderId: $request->input('work_order_id'),
            description: $request->input('description'),
            services: $request->input('services', [])
        );
    }
}
