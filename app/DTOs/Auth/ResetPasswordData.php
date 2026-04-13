<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class ResetPasswordData
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public string $passwordConfirmation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            email: $data['email'],
            password: $data['password'],
            passwordConfirmation: $data['password_confirmation'],
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            token: $request->input('token'),
            email: $request->input('email'),
            password: $request->input('password'),
            passwordConfirmation: $request->input('password_confirmation'),
        );
    }

    /**
     * Password::broker()->reset() butuh key 'password_confirmation'.
     */
    public function toArray(): array
    {
        return [
            'token'                 => $this->token,
            'email'                 => $this->email,
            'password'              => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ];
    }
}
