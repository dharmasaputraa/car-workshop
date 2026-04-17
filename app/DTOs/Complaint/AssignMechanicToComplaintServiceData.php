<?php

namespace App\DTOs\Complaint;

use Illuminate\Http\Request;

class AssignMechanicToComplaintServiceData
{
    public function __construct(
        public readonly array $mechanicIds
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            mechanicIds: $request->input('mechanic_ids', [])
        );
    }
}
