<?php

namespace App\DTOs\Car;

use Illuminate\Http\Request;

class StoreCarData
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $plateNumber,
        public readonly string $brand,
        public readonly string $model,
        public readonly int $year,
        public readonly string $color,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ownerId: $data['owner_id'],
            plateNumber: $data['plate_number'],
            brand: $data['brand'],
            model: $data['model'],
            year: (int) $data['year'],
            color: $data['color'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            ownerId: $request->input('owner_id'),
            plateNumber: $request->input('plate_number'),
            brand: $request->input('brand'),
            model: $request->input('model'),
            year: (int) $request->input('year'),
            color: $request->input('color'),
        );
    }

    public function toArray(): array
    {
        return [
            'owner_id'     => $this->ownerId,
            'plate_number' => $this->plateNumber,
            'brand'        => $this->brand,
            'model'        => $this->model,
            'year'         => $this->year,
            'color'        => $this->color,
        ];
    }
}
