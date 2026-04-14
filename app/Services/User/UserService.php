<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    private const PER_PAGE = 15;

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

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }

            return $this->userRepository->loadRelations($user, ['roles']);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $this->userRepository->update($user, collect($data)->except('role')->toArray());

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

    public function changeRole(User $user, string $role): User
    {
        return DB::transaction(function () use ($user, $role) {
            $user = $this->userRepository->syncRoles($user, [$role]);

            return $this->userRepository->loadRelations($user, ['roles']);
        });
    }
}
