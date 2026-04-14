<?php

namespace App\Services\MechanicAssignment;

use App\Actions\WorkOrders\AssignMechanicToServiceAction;
use App\Actions\WorkOrders\CancelMechanicAssignmentAction;
use App\DTOs\Mechanic\MechanicAssignmentData;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MechanicAssignmentService
{
    public function __construct(
        protected MechanicAssignmentRepositoryInterface $assignmentRepository,
        protected AssignMechanicToServiceAction $assignMechanicAction,
        protected CancelMechanicAssignmentAction $cancelMechanicAction
    ) {}

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedAssignments(): LengthAwarePaginator
    {
        return $this->assignmentRepository->getPaginatedAssignments();
    }

    public function getAssignmentById(string $id): MechanicAssignment
    {
        return $this->assignmentRepository->findById($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function createAssignment(MechanicAssignmentData $data): MechanicAssignment
    {
        return $this->assignMechanicAction->execute(
            $data->work_order_service_id,
            $data->mechanic_id
        );
    }

    public function updateAssignment(MechanicAssignment $assignment, array $data): MechanicAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            return $this->assignmentRepository->update($assignment, $data);
        });
    }

    public function deleteAssignment(MechanicAssignment $assignment): void
    {
        $this->assignmentRepository->delete($assignment);
    }

    public function cancelAssignment(string $id): MechanicAssignment
    {
        return $this->cancelMechanicAction->execute($id);
    }
}
