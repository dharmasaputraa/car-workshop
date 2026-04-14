<?php

namespace App\DTOs\Service;

use Illuminate\Http\Request;

class UpdateServiceData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?float $basePrice,
        public readonly ?bool $isActive,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            basePrice: isset($data['base_price']) ? (float) $data['base_price'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            basePrice: $request->has('base_price') ? (float) $request->input('base_price') : null,
            isActive: $request->has('is_active') ? (bool) $request->input('is_active') : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'        => $this->name,
            'description' => $this->description,
            'base_price'  => $this->basePrice,
            'is_active'   => $this->isActive,
        ], fn($value) => $value !== null);
    }
}
