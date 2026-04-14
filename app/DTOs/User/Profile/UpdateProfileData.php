<?php

namespace App\DTOs\User\Profile;

use Illuminate\Http\Request;

class UpdateProfileData
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
        ], fn($value) => !is_null($value));
    }
}
