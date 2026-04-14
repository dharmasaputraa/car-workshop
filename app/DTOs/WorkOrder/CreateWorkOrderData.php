<?php

namespace App\DTOs\WorkOrder;

use Illuminate\Http\Request;

class CreateWorkOrderData
{
    public function __construct(
        public string  $carId,
        public ?string $diagnosisNotes = null,
        public ?string $estimatedCompletion = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            carId: $data['car_id'],
            diagnosisNotes: $data['diagnosis_notes'] ?? null,
            estimatedCompletion: $data['estimated_completion'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            carId: $request->input('car_id'),
            diagnosisNotes: $request->input('diagnosis_notes'),
            estimatedCompletion: $request->input('estimated_completion'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'car_id'               => $this->carId,
            'diagnosis_notes'      => $this->diagnosisNotes,
            'estimated_completion' => $this->estimatedCompletion,
        ], fn($value) => $value !== null);
    }
}
