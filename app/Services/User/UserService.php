<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserService
{
    private const PER_PAGE = 15;

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedUsers(): LengthAwarePaginator
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::exact('is_active'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::scope('role', 'whereRoleName'),
            )
            ->allowedSorts('name', 'email', 'created_at', 'is_active')
            ->allowedIncludes('roles')
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function getTrashedUsers(): LengthAwarePaginator
    {
        return QueryBuilder::for(User::onlyTrashed())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
            )
            ->allowedSorts('name', 'deleted_at')
            ->defaultSort('-deleted_at')
            ->paginate(request()->integer('per_page', self::PER_PAGE))
            ->appends(request()->query());
    }

    public function getUserById(string $id): User
    {
        return QueryBuilder::for(User::class)
            ->allowedIncludes('roles')
            ->findOrFail($id);
    }

    public function getUserTrashedById(string $id): User
    {
        return User::withTrashed()->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ]);

            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }

            return $user->load('roles');
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update(collect($data)->except('role')->toArray());

            return $user->fresh('roles');
        });
    }

    public function deleteUser(User $user): void
    {
        $user->delete(); // soft delete
    }

    public function restoreUser(string $id): User
    {
        $user = $this->getUserTrashedById($id);

        $user->restore();

        return $user->fresh('roles');
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);

        return $user->fresh();
    }

    public function changeRole(User $user, string $role): User
    {
        return DB::transaction(function () use ($user, $role) {
            $user->syncRoles([$role]);

            return $user->fresh('roles');
        });
    }
}
