<?php

namespace App\Services\User;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class UserService
{
    public function __construct(
        protected User $user
    ) {}

    /**
     * Get paginated users formatted for JSON:API standard.
     *
     * @param array $params Query parameters from $request->query()
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(array $params = []): LengthAwarePaginator
    {
        $query = $this->user->query();

        // 1. Handle relationships (?include=roles)
        $this->applyIncludes($query, $params['include'] ?? '');

        // 2. Handle filtering (?filter[search]=john&filter[is_active]=true)
        if (!empty($params['filter']) && is_array($params['filter'])) {
            $this->applyFilters($query, $params['filter']);
        }

        // 3. Handle sorting (?sort=-created_at,name)
        $this->applySorting($query, $params['sort'] ?? '');

        // 4. Handle JSON:API pagination (?page[size]=15&page[number]=2)
        $perPage = $params['page']['size'] ?? 15;

        return $query->paginate(
            perPage: $perPage,
            columns: ['*'],
            pageName: 'page[number]'
        );
    }

    /**
     * Retrieve a specific user by ID.
     * Useful for detail endpoints, supporting eager loaded includes.
     *
     * @param string $id
     * @param array $params Query parameters to support ?include=roles
     * @return User
     * @throws ModelNotFoundException
     */
    public function getUserById(string $id, array $params = []): User
    {
        $query = $this->user->query();

        $this->applyIncludes($query, $params['include'] ?? '');

        return $query->findOrFail($id);
    }

    /**
     * Create a new user record.
     *
     * @param array $data Validated user data
     * @return User
     */
    public function createUser(array $data): User
    {
        $user = $this->user->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (array_key_exists('avatar_url', $data)) {
            $userData['avatar_url'] = $data['avatar_url'];
        }

        $user = $this->user->create($userData);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if (!array_key_exists('avatar_url', $data) && !empty($data['avatar'])) {
            $user->addMedia($data['avatar'])
                ->toMediaCollection('avatars');
        }

        // Return the user with eager loaded roles for the API response
        return $user->load('roles');
    }

    /**
     * Update an existing user.
     *
     * @param User $user The authorized user object retrieved by the Controller
     * @param array $data Validated update data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ];

        if (isset($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (array_key_exists('avatar_url', $data)) {
            $updateData['avatar_url'] = $data['avatar_url'];
        }

        $user->update($updateData);

        if (!array_key_exists('avatar_url', $data) && !empty($data['avatar'])) {
            $user->clearMediaCollection('avatars');
            $user->addMedia($data['avatar'])
                ->toMediaCollection('avatars');
        }

        return $user->load('roles');
    }

    /**
     * Perform a soft delete on the user.
     *
     * @param User $user
     * @return bool|null
     */
    public function deleteUser(User $user): ?bool
    {
        return $user->delete();
    }

    /**
     * Retrieve a paginated list of soft-deleted (trashed) users.
     *
     * @param array $params Query parameters
     * @return LengthAwarePaginator
     */
    public function getTrashedUsers(array $params = []): LengthAwarePaginator
    {
        // Force the filter to only fetch trashed records
        $params['filter']['trashed'] = 'only';

        return $this->getPaginatedUsers($params);
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param string $id
     * @return bool
     */
    public function restoreUser(string $id): bool
    {
        $user = $this->user->withTrashed()->findOrFail($id);
        return $user->restore();
    }

    /**
     * Permanently delete a user from the database.
     *
     * @param string $id
     * @return bool|null
     */
    public function forceDeleteUser(string $id): ?bool
    {
        $user = $this->user->withTrashed()->findOrFail($id);
        return $user->forceDelete();
    }

    /**
     * Change the role assigned to the user.
     *
     * @param User $user
     * @param string $role
     * @return User
     * @throws InvalidArgumentException
     */
    public function changeRole(User $user, string $role): User
    {
        $validRoles = array_column(RoleType::cases(), 'value');
        if (!in_array($role, $validRoles)) {
            throw new InvalidArgumentException("Invalid role provided: {$role}");
        }

        // syncRoles menerima array, dan otomatis menghapus role lama
        // serta menggantinya dengan role yang baru diinputkan.
        $user->syncRoles([$role]);

        return $user->load('roles');
    }
    /**
     * Disable Two-Factor Authentication for the user.
     *
     * @param User $user
     * @return User
     */
    public function disableTwoFactorAuth(User $user): User
    {
        $user->disableTwoFactorAuthentication();
        return $user->fresh();
    }

    /**
     * Completely clear all Two-Factor Authentication data for the user.
     *
     * @param User $user
     * @return User
     */
    public function clearTwoFactorAuth(User $user): User
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return $user->fresh();
    }

    /**
     * Toggle the active status of the user (active/inactive).
     *
     * @param User $user
     * @return User
     */
    public function toggleActive(User $user): User
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return $user->fresh();
    }



    /* =========================================================
        PRIVATE / PROTECTED METHODS (Query Builders)
    ========================================================= */

    /**
     * Apply custom filtering logic to the query.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_verified'])) {
            $isVerified = filter_var($filters['is_verified'], FILTER_VALIDATE_BOOLEAN);
            if ($isVerified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if (!empty($filters['trashed'])) {
            match ($filters['trashed']) {
                'with' => $query->withTrashed(),
                'only' => $query->onlyTrashed(),
                default => $query,
            };
        }
    }

    /**
     * Apply JSON:API eager loading securely.
     * Prevents users from including unauthorized sensitive relations.
     *
     * @param Builder $query
     * @param string $includeParam Comma-separated string of relations
     * @return void
     */
    protected function applyIncludes(Builder $query, string $includeParam): void
    {
        if (empty($includeParam)) return;

        // SECURITY: Define explicitly allowed relations here
        $allowedIncludes = ['roles'];

        $includes = explode(',', $includeParam);
        $validIncludes = array_intersect($includes, $allowedIncludes);

        if (!empty($validIncludes)) {
            $query->with($validIncludes);
        }
    }

    /**
     * Apply JSON:API standard sorting.
     * Prefixing the field with a minus sign (-) indicates descending order.
     *
     * @param Builder $query
     * @param string $sortParam Comma-separated string of columns
     * @return void
     */
    protected function applySorting(Builder $query, string $sortParam): void
    {
        if (empty($sortParam)) {
            // Apply default sorting if no parameter is provided
            $query->latest();
            return;
        }

        // SECURITY: Define explicitly allowed sortable columns here
        $allowedSorts = ['name', 'email', 'created_at', 'is_active'];
        $sorts = explode(',', $sortParam);

        foreach ($sorts as $sortField) {
            $direction = str_starts_with($sortField, '-') ? 'desc' : 'asc';
            $column = ltrim($sortField, '-'); // Remove the minus sign

            if (in_array($column, $allowedSorts)) {
                $query->orderBy($column, $direction);
            }
        }
    }
}
