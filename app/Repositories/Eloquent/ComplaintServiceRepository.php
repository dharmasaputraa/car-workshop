<?php

namespace App\Repositories\Eloquent;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Models\Complaint;
use App\Models\ComplaintService;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\ComplaintServiceRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ComplaintServiceRepository implements ComplaintServiceRepositoryInterface
{
    public function __construct(
        protected ServiceRepositoryInterface $serviceRepository
    ) {}

    /**
     * Add services to a complaint.
     * For each service_id, fetches the Service model to get price,
     * sets status to PENDING, and creates ComplaintService records.
     */
    public function addServicesToComplaint(Complaint $complaint, array $servicesData): void
    {
        foreach ($servicesData as $serviceData) {
            $serviceId = $serviceData['service_id'];
            $description = $serviceData['description'] ?? null;

            // Fetch the Service to get base_price
            $service = $this->serviceRepository->findById($serviceId);

            // Create ComplaintService with auto-fetched price and PENDING status
            $complaint->complaintServices()->create([
                'service_id' => $serviceId,
                'price' => $service->base_price,
                'status' => ServiceItemStatus::PENDING->value,
                'description' => $description,
            ]);
        }
    }

    /**
     * Find a ComplaintService by ID.
     */
    public function findById(string $id): ComplaintService
    {
        return ComplaintService::with('complaint')->findOrFail($id);
    }

    /**
     * Update the status of a ComplaintService.
     */
    public function updateStatus(ComplaintService $complaintService, string $status): ComplaintService
    {
        $complaintService->update(['status' => $status]);
        return $complaintService;
    }

    /**
     * Assign multiple mechanics to a complaint service.
     * Creates MechanicAssignment records for each mechanic and updates the complaint service status to IN_PROGRESS.
     */
    public function assignMechanic(ComplaintService $complaintService, array $mechanicIds): ComplaintService
    {
        // Create mechanic assignment records for each mechanic
        foreach ($mechanicIds as $mechanicId) {
            // Check for duplicate assignment
            $existingAssignment = $complaintService->mechanicAssignments()
                ->where('mechanic_id', $mechanicId)
                ->where('status', '!=', MechanicAssignmentStatus::CANCELED->value)
                ->first();

            if ($existingAssignment) {
                // Skip this mechanic if already assigned
                continue;
            }

            $complaintService->mechanicAssignments()->create([
                'mechanic_id' => $mechanicId,
                'status' => MechanicAssignmentStatus::ASSIGNED->value,
                'assigned_at' => now(),
            ]);
        }

        // Update complaint service status to IN_PROGRESS
        $complaintService->update([
            'status' => ServiceItemStatus::IN_PROGRESS->value,
        ]);

        // Load mechanics and service relationships for the response
        return $complaintService->load('mechanics', 'service');
    }

    /**
     * Get all complaint services for a specific mechanic.
     * Queries through mechanic_assignments table.
     */
    public function getByMechanicId(string $mechanicId): Collection
    {
        return ComplaintService::whereHas('mechanicAssignments', function ($query) use ($mechanicId) {
            $query->where('mechanic_id', $mechanicId);
        })->get();
    }

    /**
     * Check if all complaint services for a complaint are completed.
     */
    public function areAllServicesCompleted(Complaint $complaint): bool
    {
        $totalServices = $complaint->complaintServices()->count();
        $completedServices = $complaint->complaintServices()
            ->where('status', ServiceItemStatus::COMPLETED->value)
            ->count();

        return $totalServices > 0 && $totalServices === $completedServices;
    }
}
