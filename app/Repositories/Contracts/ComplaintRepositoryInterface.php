<?php

namespace App\Repositories\Contracts;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    /**
     * Get all complaints with role-based filtering.
     */
    public function getAll(): Collection;

    /**
     * Get paginated complaints with role-based filtering.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a complaint by ID.
     */
    public function findById(string $id): Complaint;

    /**
     * Create a new complaint.
     */
    public function create(array $data): Complaint;

    /**
     * Update a complaint.
     */
    public function update(Complaint $complaint, array $data): Complaint;

    /**
     * Delete a complaint.
     */
    public function delete(Complaint $complaint): bool;

    /**
     * Update complaint status with timestamp.
     */
    public function updateStatus(Complaint $complaint, string $status): Complaint;

    /**
     * Load missing relations.
     */
    public function loadMissingRelations(Complaint $complaint, array $relations): Complaint;

    /**
     * Find complaint by work order ID.
     */
    public function findByWorkOrderId(string $workOrderId): ?Complaint;

    /**
     * Get complaints for a specific mechanic.
     */
    public function getByMechanicId(string $mechanicId): Collection;

    /**
     * Get complaints for a specific customer (via their work orders).
     */
    public function getByCustomerId(string $customerId): Collection;
}
