<?php

namespace App\DTOs\WorkOrder;

use Illuminate\Http\Request;

class AssignMechanicData
{
    public function __construct(
        public string $mechanicId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            mechanicId: $request->input('mechanic_id'),
        );
    }
}
