<?php

namespace App\Services\User;

use App\DTOs\User\ChangeRoleData;
use App\DTOs\User\StoreUserData;
use App\DTOs\User\UpdateUserData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedUsers(): LengthAwarePaginator
    {
        return $this->userRepository->getPaginatedUsers();
    }

    public function getTrashedUsers(): LengthAwarePaginator
    {
        return $this->userRepository->getTrashedUsers();
    }

    public function getUserById(string $id): User
    {
        return $this->userRepository->findById($id);
    }

    public function getUserTrashedById(string $id): User
    {
        return $this->userRepository->findTrashedById($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function createUser(StoreUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => $data->isActive,
            ]);

            if ($data->role !== null) {
                $user->assignRole($data->role);
            }

            return $this->userRepository->loadRelations($user, ['roles']);
        });
    }

    public function updateUser(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $this->userRepository->update($user, $data->toArray());

            return $user->fresh('roles');
        });
    }

    public function deleteUser(User $user): void
    {
        $this->userRepository->delete($user);
    }

    public function restoreUser(string $id): User
    {
        $user = $this->getUserTrashedById($id);

        $this->userRepository->restore($user);

        return $this->userRepository->loadRelations($user, ['roles']);
    }

    public function toggleActive(User $user): User
    {
        $user = $this->userRepository->toggleActive($user);

        return $user->fresh();
    }

    public function changeRole(User $user, ChangeRoleData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user = $this->userRepository->syncRoles($user, [$data->role]);

            return $this->userRepository->loadRelations($user, ['roles']);
        });
    }
}
