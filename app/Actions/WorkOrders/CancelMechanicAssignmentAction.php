<?php

namespace App\Actions\WorkOrders;

use App\Enums\MechanicAssignmentStatus;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use Exception;

class CancelMechanicAssignmentAction
{
    public function __construct(
        protected MechanicAssignmentRepositoryInterface $assignmentRepository
    ) {}

    public function execute(string $id): MechanicAssignment
    {
        $assignment = $this->assignmentRepository->findById($id);

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

        // (Opsional) Trigger event jika ingin mengirim notifikasi email ke mekanik
        // bahwa tugasnya dibatalkan
        // MechanicAssignmentCanceled::dispatch($assignment);

        return $assignment;
    }
}
