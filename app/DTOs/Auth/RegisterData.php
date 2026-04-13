<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class RegisterData
{
    public function __construct(
        public string  $name,
        public string  $email,
        public string  $password,
        public string $passwordConfirmation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            passwordConfirmation: $data['password_confirmation'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
            passwordConfirmation: $request->input('password_confirmation'),
        );
    }

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ];
    }
}
