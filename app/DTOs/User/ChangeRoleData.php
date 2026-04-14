<?php

namespace App\DTOs\User;

use Illuminate\Http\Request;

class ChangeRoleData
{
    public function __construct(
        public readonly string $role,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            role: $data['role'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            role: $request->input('role'),
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
        ];
    }
}
