<?php

namespace App\Http\Controllers\Api\V1\Complaint;

use App\Actions\Complaints\ReassignComplaintAction;
use App\Actions\Complaints\RejectComplaintAction;
use App\Actions\Complaints\ResolveComplaintAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Complaint\AssignMechanicToComplaintServiceRequest;
use App\Http\Resources\Api\V1\Complaint\ComplaintResource;
use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintServiceRepositoryInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Workshop Ops - Complaints')]
class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaintRepository,
        protected ComplaintServiceRepositoryInterface $complaintServiceRepository
    ) {}

    /**
     * List Complaints
     *
     * Retrieve a paginated list of complaints with filtering, sorting, and eager loading support.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Complaint::class);

        return ComplaintResource::collection(
            $this->complaintRepository->getPaginated()
        );
    }

    /**
     * Get Complaint
     *
     * Retrieve a single complaint detail by its UUID.
     */
    public function show(string $id): ComplaintResource
    {
        $complaint = $this->complaintRepository->findById($id);
        Gate::authorize('view', $complaint);

        return new ComplaintResource($complaint);
    }

    /**
     * Reassign Complaint
     *
     * Reassign a complaint for rework. Transitions complaint from PENDING to IN_PROGRESS
     * and work order from COMPLAINED to IN_PROGRESS.
     */
    public function reassign(string $id, ReassignComplaintAction $action): ComplaintResource
    {
        $complaint = $this->complaintRepository->findById($id);
        Gate::authorize('reassign', $complaint);

        $complaint = $action->execute($id);

        return new ComplaintResource($complaint);
    }

    /**
     * Resolve Complaint
     *
     * Mark a complaint as resolved. Only allowed when all complaint services are completed.
     * Transitions complaint from IN_PROGRESS to RESOLVED and work order back to COMPLETED.
     */
    public function resolve(string $id, ResolveComplaintAction $action): ComplaintResource
    {
        $complaint = $this->complaintRepository->findById($id);
        Gate::authorize('resolve', $complaint);

        $complaint = $action->execute($id);

        return new ComplaintResource($complaint);
    }

    /**
     * Reject Complaint
     *
     * Reject a complaint. Transitions complaint from PENDING/IN_PROGRESS to REJECTED
     * and work order back to COMPLETED.
     */
    public function reject(string $id, RejectComplaintAction $action): ComplaintResource
    {
        $complaint = $this->complaintRepository->findById($id);
        Gate::authorize('reject', $complaint);

        $complaint = $action->execute($id);

        return new ComplaintResource($complaint);
    }

    /**
     * Assign Mechanic to Complaint Service
     *
     * Assign multiple mechanics to a specific complaint service.
     */
    public function assignMechanic(
        string $complaintServiceId,
        AssignMechanicToComplaintServiceRequest $request
    ): JsonResponse {
        $complaintService = $this->complaintServiceRepository->findById($complaintServiceId);
        Gate::authorize('assignMechanic', $complaintService->complaint);

        $dto = \App\DTOs\Complaint\AssignMechanicToComplaintServiceData::fromRequest($request);
        $complaintService = $this->complaintServiceRepository->assignMechanic($complaintService, $dto->mechanicIds);

        return (new \App\Http\Resources\Api\V1\Complaint\ComplaintServiceResource($complaintService))
            ->response()
            ->setStatusCode(200);
    }
}
