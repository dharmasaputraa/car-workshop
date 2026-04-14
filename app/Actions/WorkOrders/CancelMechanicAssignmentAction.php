<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Exception;

class CancelMechanicAssignmentAction
{
    public function __construct(
        protected MechanicAssignmentRepositoryInterface $assignmentRepository,
        protected WorkOrderServiceRepositoryInterface $workOrderServiceRepository
    ) {}

    public function execute(string $id): MechanicAssignment
    {
        $assignment = $this->assignmentRepository->findById($id);
        $woService = $assignment->workOrderService;

        // Daftar status yang TIDAK BOLEH di-cancel
        $uncancelableStates = [
            MechanicAssignmentStatus::COMPLETED,
            MechanicAssignmentStatus::CANCELED,
        ];

        if (in_array($assignment->status, $uncancelableStates)) {
            throw new Exception("Cannot cancel Mechanic Assignments that have been completed or already canceled.");
        }

        // Ubah status menjadi CANCELED
        $assignment = $this->assignmentRepository->update($assignment, [
            'status' => MechanicAssignmentStatus::CANCELED->value
        ]);

        // Check if the WorkOrderService still has any active assignments
        // If not, revert the service status back to PENDING
        if (!$this->workOrderServiceRepository->hasActiveAssignments($woService)) {
            $this->workOrderServiceRepository->updateStatus($woService, ServiceItemStatus::PENDING->value);
        }

        // (Opsional) Trigger event jika ingin mengirim notifikasi email ke mekanik
        // bahwa tugasnya dibatalkan
        // MechanicAssignmentCanceled::dispatch($assignment);

        return $assignment;
    }
}
