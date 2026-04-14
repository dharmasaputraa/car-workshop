<?php

namespace App\DTOs\Car;

use Illuminate\Http\Request;

class UpdateCarData
{
    public function __construct(
        public readonly ?string $ownerId,
        public readonly ?string $plateNumber,
        public readonly ?string $brand,
        public readonly ?string $model,
        public readonly ?int $year,
        public readonly ?string $color,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ownerId: $data['owner_id'] ?? null,
            plateNumber: $data['plate_number'] ?? null,
            brand: $data['brand'] ?? null,
            model: $data['model'] ?? null,
            year: isset($data['year']) ? (int) $data['year'] : null,
            color: $data['color'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            ownerId: $request->input('owner_id'),
            plateNumber: $request->input('plate_number'),
            brand: $request->input('brand'),
            model: $request->input('model'),
            year: $request->has('year') ? (int) $request->input('year') : null,
            color: $request->input('color'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'owner_id'     => $this->ownerId,
            'plate_number' => $this->plateNumber,
            'brand'        => $this->brand,
            'model'        => $this->model,
            'year'         => $this->year,
            'color'        => $this->color,
        ], fn($value) => $value !== null);
    }
}
