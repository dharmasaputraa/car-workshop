<?php

namespace App\DTOs\Service;

use Illuminate\Http\Request;

class StoreServiceData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $basePrice,
        public readonly bool $isActive,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            basePrice: (float) $data['base_price'],
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            basePrice: (float) $request->input('base_price'),
            isActive: (bool) $request->input('is_active', true),
        );
    }

    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'base_price'  => $this->basePrice,
            'is_active'   => $this->isActive,
        ];
    }
}
