<?php

namespace App\DTOs\User\Profile;

use Illuminate\Http\Request;

class ChangePasswordData
{
    public function __construct(
        public string $currentPassword,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currentPassword: $data['current_password'],
            password: $data['password'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            currentPassword: $request->input('current_password'),
            password: $request->input('password'),
        );
    }

    public function toArray(): array
    {
        return [
            'current_password' => $this->currentPassword,
            'password' => $this->password,
        ];
    }
}
