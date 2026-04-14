<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated users with filtering and sorting support.
     */
    public function getPaginatedUsers(): LengthAwarePaginator;

    /**
     * Get paginated trashed users with filtering and sorting support.
     */
    public function getTrashedUsers(): LengthAwarePaginator;

    /**
     * Find a user by ID.
     */
    public function findById(string $id): User;

    /**
     * Find a trashed user by ID.
     */
    public function findTrashedById(string $id): User;

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): User;

    /**
     * Delete (soft delete) a user.
     */
    public function delete(User $user): void;

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user): User;

    /**
     * Toggle the is_active status of a user.
     */
    public function toggleActive(User $user): User;

    /**
     * Sync roles for a user.
     */
    public function syncRoles(User $user, array $roles): User;

    /*
    |--------------------------------------------------------------------------
    | RELATIONS & MEDIA
    |--------------------------------------------------------------------------
    */

    /**
     * Load relationships on a user model.
     */
    public function loadRelations(User $user, array $relations): User;

    /**
     * Load missing relationships on a user model.
     */
    public function loadMissingRelations(User $user, array $relations): User;

    /**
     * Clear media collection for a user.
     */
    public function clearMediaCollection(User $user, string $collection): void;

    /**
     * Add media to a user's collection.
     */
    public function addMedia(User $user, mixed $file, string $collection, string $disk): mixed;
}
