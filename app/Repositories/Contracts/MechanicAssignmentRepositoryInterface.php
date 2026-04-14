<?php

namespace App\Repositories\Contracts;

use App\Models\MechanicAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MechanicAssignmentRepositoryInterface
{
    public function getPaginatedAssignments(): LengthAwarePaginator;
    public function findById(string $id): MechanicAssignment;
    public function create(array $data): MechanicAssignment;
    public function update(MechanicAssignment $assignment, array $data): MechanicAssignment;
    public function delete(MechanicAssignment $assignment): void;
}
