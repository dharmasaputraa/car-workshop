<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserRepository implements UserRepositoryInterface
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

    public function findById(string $id): User
    {
        return QueryBuilder::for(User::class)
            ->allowedIncludes('roles')
            ->findOrFail($id);
    }

    public function findTrashedById(string $id): User
    {
        return User::withTrashed()->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete(); // soft delete
    }

    public function restore(User $user): User
    {
        $user->restore();

        return $user;
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);

        return $user;
    }

    public function syncRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS & MEDIA
    |--------------------------------------------------------------------------
    */

    public function loadRelations(User $user, array $relations): User
    {
        return $user->load($relations);
    }

    public function loadMissingRelations(User $user, array $relations): User
    {
        return $user->loadMissing($relations);
    }

    public function clearMediaCollection(User $user, string $collection): void
    {
        $user->clearMediaCollection($collection);
    }

    public function addMedia(User $user, mixed $file, string $collection, string $disk): mixed
    {
        return $user->addMedia($file)
            ->toMediaCollection($collection, $disk);
    }
}
