<?php

namespace App\DTOs\User;

use Illuminate\Http\Request;

class UpdateUserData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $password,
        public readonly ?bool $isActive,
        public readonly ?string $avatarUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            avatarUrl: $data['avatar_url'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
            isActive: $request->has('is_active') ? (bool) $request->input('is_active') : null,
            avatarUrl: $request->input('avatar_url'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => $this->isActive,
            'avatar_url' => $this->avatarUrl,
        ], fn($value) => $value !== null);
    }
}
