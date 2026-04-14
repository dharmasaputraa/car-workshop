<?php

namespace App\DTOs\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderData
{
    public function __construct(
        public readonly ?string $carId,
        public readonly ?string $diagnosisNotes,
        public readonly ?string $estimatedCompletion,
        protected readonly array $validatedData // Menyimpan data yang benar-benar dikirim (PATCH)
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            carId: $request->validated('car_id'),
            diagnosisNotes: $request->validated('diagnosis_notes'),
            estimatedCompletion: $request->validated('estimated_completion'),
            validatedData: $request->validated()
        );
    }

    /**
     * Mengembalikan array yang HANYA berisi field yang dikirim oleh client.
     */
    public function toArray(): array
    {
        return $this->validatedData;
    }
}
