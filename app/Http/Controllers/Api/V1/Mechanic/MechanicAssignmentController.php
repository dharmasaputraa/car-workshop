<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\DTOs\Mechanic\MechanicAssignmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mechanic\StoreMechanicAssignmentRequest;
use App\Http\Requests\Api\V1\Mechanic\UpdateMechanicAssignmentRequest;
use App\Http\Resources\Api\V1\Mechanic\MechanicAssignmentResource;
use App\Models\MechanicAssignment;
use App\Repositories\Contracts\MechanicAssignmentRepositoryInterface;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\UrlParam;

#[Group('Workshop Ops - Mechanic Assignments')]
class MechanicAssignmentController extends Controller
{
    public function __construct(
        private readonly MechanicAssignmentRepositoryInterface $assignmentRepository
    ) {}

    /**
     * List Mechanic Assignments
     *
     * Retrieve a paginated list of mechanic assignments.
     */
    #[QueryParameter('filter[status]', description: 'Filter by assignment status', type: 'string', example: 'in_progress')]
    #[QueryParameter('filter[mechanic_id]', description: 'Filter by mechanic UUID', type: 'string', example: 'uuid-here')]
    #[QueryParameter('filter[work_order_service_id]', description: 'Filter by specific work order service UUID', type: 'string', example: 'uuid-here')]
    #[QueryParameter('sort', description: 'Sort by field. Options: assigned_at, completed_at, created_at', type: 'string', example: '-assigned_at')]
    #[QueryParameter('include', description: 'Include relations: mechanic, workOrderService', type: 'string', example: 'mechanic')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MechanicAssignment::class);

        return MechanicAssignmentResource::collection(
            $this->assignmentRepository->getPaginatedAssignments()
        );
    }

    /**
     * Get Mechanic Assignment
     *
     * Retrieve details of a specific assignment.
     */
    #[QueryParameter('include', description: 'Include relations: mechanic, workOrderService', type: 'string', example: 'mechanic,workOrderService')]
    public function show(string $id): MechanicAssignmentResource
    {
        $assignment = $this->assignmentRepository->findById($id);
        Gate::authorize('view', $assignment);

        return new MechanicAssignmentResource($assignment);
    }

    /**
     * Create Mechanic Assignment
     *
     * Directly assign a mechanic to a work order service item.
     */
    public function store(StoreMechanicAssignmentRequest $request): MechanicAssignmentResource
    {
        Gate::authorize('create', MechanicAssignment::class);

        // Transform validated request to DTO
        $dto = MechanicAssignmentData::fromRequest($request);

        // Create via repository (Nantinya logic ini idealnya dibungkus di MechanicService)
        $assignment = $this->assignmentRepository->create($dto->toArray());

        return new MechanicAssignmentResource($assignment);
    }

    /**
     * Update Mechanic Assignment Status
     *
     * Update the progress of an assignment (e.g., mark as completed).
     */
    public function update(UpdateMechanicAssignmentRequest $request, string $id): MechanicAssignmentResource
    {
        $assignment = $this->assignmentRepository->findById($id);
        Gate::authorize('update', $assignment);

        $assignment = $this->assignmentRepository->update($assignment, $request->validated());

        return new MechanicAssignmentResource($assignment);
    }

    /**
     * Delete Mechanic Assignment
     *
     * Remove a mechanic from a service item.
     */
    public function destroy(string $id): JsonResponse
    {
        $assignment = $this->assignmentRepository->findById($id);
        Gate::authorize('delete', $assignment);

        $this->assignmentRepository->delete($assignment);

        return response()->json(null, 204);
    }
}
