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

    public static function fromRequest(Request $request, string $workOrderId): self
    {
        return new self(
            workOrderId: $workOrderId,
            description: $request->input('description'),
            services: $request->input('services', [])
        );
    }
}
