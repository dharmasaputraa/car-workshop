<?php

namespace App\DTOs\WorkOrder;

use Illuminate\Http\Request;

class AssignMechanicData
{
    public function __construct(
        public array $mechanicIds,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            mechanicIds: $request->input('mechanic_ids', []),
        );
    }
}
