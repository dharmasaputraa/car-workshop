<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterData;
use App\Events\UserRegistered;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user.
     */
    public function execute(RegisterData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => true,
            ]);

            event(new UserRegistered($user));

            return $user;
        });
    }
}
