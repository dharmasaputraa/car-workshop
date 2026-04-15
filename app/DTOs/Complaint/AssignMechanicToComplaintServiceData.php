<?php

namespace App\DTOs\Complaint;

use Illuminate\Http\Request;

class AssignMechanicToComplaintServiceData
{
    public function __construct(
        public readonly string $mechanicId
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            mechanicId: $request->input('mechanic_id')
        );
    }
}
