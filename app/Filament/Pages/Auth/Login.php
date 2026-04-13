<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $response = parent::authenticate();

        $user = Filament::auth()->user();

        if ($user && ! $user->is_active) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'Your account is inactive.',
            ]);
        }

        return $response;
    }
}
