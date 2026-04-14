<?php

namespace App\DTOs\WorkOrder;

use Illuminate\Http\Request;

class DiagnoseWorkOrderData
{
    public function __construct(
        public ?string $diagnosisNotes = null,
        public array   $services = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            diagnosisNotes: $request->input('diagnosis_notes'),
            services: $request->input('services', []),
        );
    }
}
