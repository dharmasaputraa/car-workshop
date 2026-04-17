<?php

namespace App\Repositories\Contracts;

use App\Models\Complaint;
use App\Models\ComplaintService;
use Illuminate\Database\Eloquent\Collection;

interface ComplaintServiceRepositoryInterface
{
    /**
     * Add services to a complaint.
     * For each service_id, fetches the Service model to get price,
     * sets status to PENDING, and creates ComplaintService records.
     */
    public function addServicesToComplaint(Complaint $complaint, array $servicesData): void;

    /**
     * Find a ComplaintService by ID.
     */
    public function findById(string $id): ComplaintService;

    /**
     * Update the status of a ComplaintService.
     */
    public function updateStatus(ComplaintService $complaintService, string $status): ComplaintService;

    /**
     * Assign multiple mechanics to a complaint service.
     */
    public function assignMechanic(ComplaintService $complaintService, array $mechanicIds): ComplaintService;

    /**
     * Get all complaint services for a specific mechanic.
     */
    public function getByMechanicId(string $mechanicId): Collection;

    /**
     * Check if all complaint services for a complaint are completed.
     */
    public function areAllServicesCompleted(Complaint $complaint): bool;
}
