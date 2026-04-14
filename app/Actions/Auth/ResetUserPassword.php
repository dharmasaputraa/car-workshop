<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class ResetUserPassword
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Handle the password reset callback from Password broker.
     * This method is called by Laravel's Password::broker()->reset().
     */
    public function execute(User $user, string $password): void
    {
        $this->userRepository->update($user, [
            'password' => $password,
            'remember_token' => Str::random(60),
        ]);

        event(new PasswordReset($user));
    }
}
